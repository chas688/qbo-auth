<?php

namespace CharlesHarmon\QboAuth;

use InvalidArgumentException;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2AccessToken;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\DataService\DataService;

class OAuthClient
{
    public function authorizationUrl(string $state): string
    {
        return $this->helper($state)->getAuthorizationCodeURL();
    }

    public function exchangeCode(string $code, string $realmId): OAuth2AccessToken
    {
        return $this->helper()->exchangeAuthorizationCodeForToken($code, $realmId);
    }

    public function refresh(string $refreshToken): OAuth2AccessToken
    {
        return $this->helper()->refreshAccessTokenWithRefreshToken($refreshToken);
    }

    public function revoke(string $token): void
    {
        $this->helper()->revokeToken($token);
    }

    /**
     * Build an SDK DataService for API calls. The SDK's request logger is disabled because it writes tokens to disk.
     */
    public function dataService(string $accessToken, string $realmId): DataService
    {
        [$clientId, $clientSecret] = $this->credentials();

        $dataService = DataService::Configure([
            'auth_mode' => 'oauth2',
            'ClientID' => $clientId,
            'ClientSecret' => $clientSecret,
            'accessTokenKey' => $accessToken,
            'QBORealmID' => $realmId,
            'baseUrl' => config('qbo-auth.environment') === 'production' ? 'Production' : 'Development',
        ]);

        $dataService->disableLog();

        return $dataService;
    }

    /** @return array{string, string, string} */
    private function credentials(): array
    {
        $clientId = config('qbo-auth.client_id');
        $clientSecret = config('qbo-auth.client_secret');
        $redirectUri = config('qbo-auth.redirect_uri');

        if (! is_string($clientId) || $clientId === '' || ! is_string($clientSecret) || $clientSecret === '' || ! is_string($redirectUri) || $redirectUri === '') {
            throw new InvalidArgumentException('Configure QBO_CLIENT_ID, QBO_CLIENT_SECRET, and QBO_REDIRECT_URI.');
        }

        return [$clientId, $clientSecret, $redirectUri];
    }

    private function helper(?string $state = null): OAuth2LoginHelper
    {
        [$clientId, $clientSecret, $redirectUri] = $this->credentials();

        return new OAuth2LoginHelper(
            $clientId,
            $clientSecret,
            $redirectUri,
            config('qbo-auth.scope'),
            $state,
        );
    }
}
