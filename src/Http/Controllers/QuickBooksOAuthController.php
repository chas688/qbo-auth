<?php

namespace CharlesHarmon\QboAuth\Http\Controllers;

use CharlesHarmon\QboAuth\CompanyInfoClient;
use CharlesHarmon\QboAuth\Models\QuickBooksConnection;
use CharlesHarmon\QboAuth\OAuthClient;
use CharlesHarmon\QboAuth\QuickBooksConnections;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use QuickBooksOnline\API\Exception\SdkException;
use QuickBooksOnline\API\Exception\ServiceException;
use RuntimeException;

class QuickBooksOAuthController
{
    public function index(Request $request, QuickBooksConnections $connections): View|RedirectResponse
    {
        $hostRedirect = $this->redirectToCallbackHost($request, 'qbo-auth.index');

        if ($hostRedirect !== null) {
            return $hostRedirect;
        }

        return view('qbo-auth::connect', [
            'environment' => config('qbo-auth.environment', 'sandbox'),
            'connections' => $connections->all(),
            'status' => $request->session()->get('qbo-auth.status'),
        ]);
    }

    public function connect(Request $request, OAuthClient $client): RedirectResponse
    {
        $hostRedirect = $this->redirectToCallbackHost($request, 'qbo-auth.connect');

        if ($hostRedirect !== null) {
            return $hostRedirect;
        }

        $state = Str::random(40);
        $request->session()->put('qbo-auth.state', $state);
        $request->session()->put('qbo-auth.popup', $request->boolean('popup'));

        $this->log('connect_started', [
            'request_host' => $request->getHost(),
            'popup' => $request->boolean('popup'),
        ]);

        return redirect()->away($client->authorizationUrl($state));
    }

    public function callback(Request $request, OAuthClient $client, QuickBooksConnections $connections): JsonResponse|Response
    {
        $expectedState = $request->session()->pull('qbo-auth.state');
        $receivedState = $request->query('state');
        $isPopup = $request->session()->pull('qbo-auth.popup', false) === true;

        if (! is_string($expectedState) || ! is_string($receivedState) || ! hash_equals($expectedState, $receivedState)) {
            $this->log('callback_state_rejected', [
                'request_host' => $request->getHost(),
                'callback_host' => parse_url((string) config('qbo-auth.redirect_uri'), PHP_URL_HOST),
                'session_cookie_present' => $request->hasCookie((string) config('session.cookie')),
                'expected_state_present' => is_string($expectedState),
                'received_state_present' => is_string($receivedState),
            ]);

            return $this->callbackResponse($request, $isPopup, 'Invalid OAuth state.', 400);
        }

        if ($request->query('error')) {
            $this->log('callback_declined', ['request_host' => $request->getHost()]);

            return $this->callbackResponse($request, $isPopup, 'QuickBooks authorization was declined.', 400);
        }

        $code = $request->query('code');
        $realmId = $request->query('realmId');

        if (! is_string($code) || $code === '' || ! is_string($realmId) || $realmId === '') {
            $this->log('callback_missing_details', ['request_host' => $request->getHost()]);

            return $this->callbackResponse($request, $isPopup, 'Missing QuickBooks authorization details.', 400);
        }

        $this->log('callback_token_exchange_started', ['request_host' => $request->getHost()]);

        $token = $client->exchangeCode($code, $realmId);
        $connection = $connections->save($realmId, $token);

        $this->log('callback_connected', [
            'request_host' => $request->getHost(),
            'environment' => $connection->environment,
        ]);

        return $this->callbackResponse($request, $isPopup, 'QuickBooks company connected.', 200, [
            'realm_id' => $connection->realm_id,
            'environment' => $connection->environment,
        ]);
    }

    /**
     * Call QuickBooks with the stored tokens, refreshing the access token first when it has expired.
     */
    public function company(Request $request, QuickBooksConnection $connection, QuickBooksConnections $connections, CompanyInfoClient $companyInfo): JsonResponse
    {
        abort_unless($connection->environment === config('qbo-auth.environment', 'sandbox'), 404);

        try {
            $freshConnection = $connections->ensureFreshAccessToken($connection);
            $company = $companyInfo->fetch($freshConnection->access_token, $freshConnection->realm_id);
        } catch (RuntimeException|SdkException|ServiceException $exception) {
            report($exception);
            $this->log('company_info_failed', ['request_host' => $request->getHost()]);

            return response()->json(['message' => 'QuickBooks could not load this company. The connection may need to be reconnected.'], 502);
        }

        $this->log('company_info_loaded', ['request_host' => $request->getHost()]);

        return response()->json([
            'realm_id' => $freshConnection->realm_id,
            'environment' => $freshConnection->environment,
            'token_refreshed' => $freshConnection !== $connection,
            'access_token_expires_at' => $freshConnection->access_token_expires_at?->toIso8601String(),
            'company' => $company,
        ]);
    }

    /**
     * Refresh the connection's tokens on demand, so the connect page shows the new expiry dates.
     */
    public function refresh(Request $request, QuickBooksConnection $connection, QuickBooksConnections $connections): RedirectResponse
    {
        abort_unless($connection->environment === config('qbo-auth.environment', 'sandbox'), 404);

        try {
            $connections->refresh($connection);
            $refreshed = true;
        } catch (SdkException|ServiceException $exception) {
            report($exception);
            $refreshed = false;
        }

        $this->log('connection_refreshed', [
            'request_host' => $request->getHost(),
            'refreshed' => $refreshed,
        ]);

        $request->session()->flash('qbo-auth.status', $refreshed
            ? ['succeeded' => true, 'message' => "QuickBooks company {$connection->realm_id} tokens refreshed."]
            : ['succeeded' => false, 'message' => "QuickBooks could not refresh company {$connection->realm_id}. Its refresh token may have expired or been revoked; connect it again."]);

        return redirect()->away(route('qbo-auth.index', [], false));
    }

    public function destroy(Request $request, QuickBooksConnection $connection, QuickBooksConnections $connections): RedirectResponse
    {
        abort_unless($connection->environment === config('qbo-auth.environment', 'sandbox'), 404);

        $revoked = $connections->disconnect($connection);

        $this->log('connection_removed', [
            'request_host' => $request->getHost(),
            'revoked' => $revoked,
        ]);

        $request->session()->flash('qbo-auth.status', $revoked
            ? ['succeeded' => true, 'message' => "QuickBooks company {$connection->realm_id} disconnected."]
            : ['succeeded' => false, 'message' => "QuickBooks company {$connection->realm_id} was removed, but QuickBooks could not revoke its access. Revoke it from the QuickBooks app settings if it is still listed there."]);

        return redirect()->away(route('qbo-auth.index', [], false));
    }

    /**
     * Send the browser to the callback host so the session cookie that holds the state lives on the host Intuit returns to.
     */
    private function redirectToCallbackHost(Request $request, string $routeName): ?RedirectResponse
    {
        $redirectUri = (string) config('qbo-auth.redirect_uri');
        $redirectHost = parse_url($redirectUri, PHP_URL_HOST);
        $redirectScheme = parse_url($redirectUri, PHP_URL_SCHEME);

        if (! is_string($redirectHost) || ! in_array($redirectScheme, ['http', 'https'], true) || $request->getHost() === $redirectHost) {
            return null;
        }

        $origin = $redirectScheme.'://'.$redirectHost;
        $port = parse_url($redirectUri, PHP_URL_PORT);

        if (is_int($port)) {
            $origin .= ':'.$port;
        }

        $this->log('connect_host_redirect', [
            'request_host' => $request->getHost(),
            'callback_host' => $redirectHost,
        ]);

        return redirect()->away($origin.route($routeName, $request->query(), false));
    }

    /**
     * Respond with JSON for a full-page flow, or with a page that notifies the opener window for a popup flow.
     *
     * @param  array<string, string>  $details
     */
    private function callbackResponse(Request $request, bool $isPopup, string $message, int $status, array $details = []): JsonResponse|Response
    {
        if (! $isPopup) {
            return response()->json(['message' => $message, ...$details], $status);
        }

        $result = ['succeeded' => $status < 400, 'message' => $message];
        $request->session()->flash('qbo-auth.status', $result);

        return response()->view('qbo-auth::popup-callback', $result, $status);
    }

    /** @param array<string, bool|string|null> $context */
    private function log(string $event, array $context = []): void
    {
        if (config('qbo-auth.logging')) {
            Log::info('QuickBooks OAuth '.$event, $context);
        }
    }
}
