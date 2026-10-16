<?php

namespace CharlesHarmon\QboAuth\Tests\Feature;

use CharlesHarmon\QboAuth\CompanyInfoClient;
use CharlesHarmon\QboAuth\Models\QuickBooksConnection;
use CharlesHarmon\QboAuth\OAuthClient;
use CharlesHarmon\QboAuth\QuickBooksConnections;
use CharlesHarmon\QboAuth\Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Mockery;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2AccessToken;
use QuickBooksOnline\API\Exception\ServiceException;
use RuntimeException;

class QuickBooksOAuthTest extends TestCase
{
    public function test_sdk_authorization_url_uses_host_credentials_and_state(): void
    {
        config()->set('qbo-auth.client_id', 'test-client');
        config()->set('qbo-auth.client_secret', 'test-secret');
        config()->set('qbo-auth.redirect_uri', 'http://localhost:8000/oauth/quickbooks/callback');

        $url = app(OAuthClient::class)->authorizationUrl('test-state');
        parse_str(parse_url($url, PHP_URL_QUERY), $parameters);

        $this->assertSame('test-client', $parameters['client_id']);
        $this->assertSame('test-state', $parameters['state']);
        $this->assertSame('http://localhost:8000/oauth/quickbooks/callback', $parameters['redirect_uri']);
        $this->assertSame('com.intuit.quickbooks.accounting', $parameters['scope']);
    }

    public function test_connect_uses_a_session_state_in_the_sdk_authorization_url(): void
    {
        config()->set('qbo-auth.redirect_uri', 'http://localhost:8000/oauth/quickbooks/callback');

        $client = Mockery::mock(OAuthClient::class);
        $client->shouldReceive('authorizationUrl')
            ->once()
            ->withArgs(fn (string $state): bool => strlen($state) === 40)
            ->andReturn('https://appcenter.intuit.com/connect/oauth2?state=test');
        $this->app->instance(OAuthClient::class, $client);

        $response = $this->get(route('qbo-auth.connect'));

        $response->assertRedirect('https://appcenter.intuit.com/connect/oauth2?state=test');
        $this->assertNotEmpty(session('qbo-auth.state'));
        $this->assertFalse(session('qbo-auth.popup'));
    }

    public function test_connect_moves_to_callback_host_before_creating_state(): void
    {
        config()->set('qbo-auth.redirect_uri', 'https://example.ngrok.app/oauth/quickbooks/callback');
        $client = Mockery::mock(OAuthClient::class);
        $client->shouldNotReceive('authorizationUrl');
        $this->app->instance(OAuthClient::class, $client);

        $this->get('http://localhost:8000/oauth/quickbooks/connect')
            ->assertRedirect('https://example.ngrok.app/oauth/quickbooks/connect');

        $this->assertNull(session('qbo-auth.state'));
    }

    public function test_callback_rejects_invalid_state_before_exchanging_a_code(): void
    {
        config()->set('qbo-auth.logging', true);
        Log::shouldReceive('info')
            ->once()
            ->with('QuickBooks OAuth callback_state_rejected', Mockery::on(function (array $context): bool {
                return $context['expected_state_present'] === true
                    && $context['received_state_present'] === true
                    && ! array_key_exists('state', $context);
            }));

        $client = Mockery::mock(OAuthClient::class);
        $client->shouldNotReceive('exchangeCode');
        $this->app->instance(OAuthClient::class, $client);

        $this->withSession(['qbo-auth.state' => 'expected'])
            ->get(route('qbo-auth.callback', ['state' => 'wrong', 'code' => 'code', 'realmId' => '123']))
            ->assertStatus(400);

        $this->assertNull(session('qbo-auth.state'));
    }

    public function test_callback_stores_both_tokens_encrypted_for_the_company(): void
    {
        $client = Mockery::mock(OAuthClient::class);
        $client->shouldReceive('exchangeCode')->once()->with('code', '123')->andReturn($this->token('access', 'refresh'));
        $this->app->instance(OAuthClient::class, $client);

        $this->withSession(['qbo-auth.state' => 'expected'])
            ->get(route('qbo-auth.callback', ['state' => 'expected', 'code' => 'code', 'realmId' => '123']))
            ->assertOk()
            ->assertJsonPath('realm_id', '123')
            ->assertDontSee('refresh');

        $connection = QuickBooksConnection::query()->firstOrFail();
        $this->assertSame('access', $connection->access_token);
        $this->assertSame('refresh', $connection->refresh_token);
        $this->assertNotSame('refresh', $connection->getRawOriginal('refresh_token'));
    }

    public function test_index_shows_popup_connect_button_and_connected_companies(): void
    {
        config()->set('qbo-auth.redirect_uri', 'http://localhost/oauth/quickbooks/callback');

        QuickBooksConnection::query()->create([
            'realm_id' => '9130',
            'environment' => 'sandbox',
            'access_token' => 'secret-access',
            'refresh_token' => 'secret-refresh',
        ]);

        $this->get(route('qbo-auth.index'))
            ->assertOk()
            ->assertSee(route('qbo-auth.connect', ['popup' => 1], false))
            ->assertSee('9130')
            ->assertDontSee('secret-access')
            ->assertDontSee('secret-refresh');
    }

    public function test_connect_moves_to_callback_host_keeping_popup_flag(): void
    {
        config()->set('qbo-auth.redirect_uri', 'https://example.ngrok.app/oauth/quickbooks/callback');

        $this->get('http://localhost:8000/oauth/quickbooks/connect?popup=1')
            ->assertRedirect('https://example.ngrok.app/oauth/quickbooks/connect?popup=1');
    }

    public function test_popup_callback_notifies_opener_and_flashes_result(): void
    {
        $client = Mockery::mock(OAuthClient::class);
        $client->shouldReceive('exchangeCode')->once()->with('code', '123')->andReturn($this->token('access', 'refresh'));
        $this->app->instance(OAuthClient::class, $client);

        $this->withSession(['qbo-auth.state' => 'expected', 'qbo-auth.popup' => true])
            ->get(route('qbo-auth.callback', ['state' => 'expected', 'code' => 'code', 'realmId' => '123']))
            ->assertOk()
            ->assertViewIs('qbo-auth::popup-callback')
            ->assertSee('QuickBooks company connected.')
            ->assertDontSee('refresh');

        $this->assertSame(['succeeded' => true, 'message' => 'QuickBooks company connected.'], session('qbo-auth.status'));
        $this->assertSame(1, QuickBooksConnection::query()->count());
    }

    public function test_popup_callback_reports_invalid_state_to_opener(): void
    {
        $client = Mockery::mock(OAuthClient::class);
        $client->shouldNotReceive('exchangeCode');
        $this->app->instance(OAuthClient::class, $client);

        $this->withSession(['qbo-auth.state' => 'expected', 'qbo-auth.popup' => true])
            ->get(route('qbo-auth.callback', ['state' => 'wrong', 'code' => 'code', 'realmId' => '123']))
            ->assertStatus(400)
            ->assertViewIs('qbo-auth::popup-callback');

        $this->assertFalse(session('qbo-auth.status')['succeeded']);
    }

    public function test_index_shows_delete_button_for_each_connection(): void
    {
        config()->set('qbo-auth.redirect_uri', 'http://localhost/oauth/quickbooks/callback');

        $connection = $this->connection('9130');

        $this->get(route('qbo-auth.index'))
            ->assertOk()
            ->assertSee(route('qbo-auth.connections.destroy', $connection, false))
            ->assertSee('Remove this connection?');
    }

    public function test_destroy_revokes_tokens_and_deletes_the_connection(): void
    {
        $client = Mockery::mock(OAuthClient::class);
        $client->shouldReceive('revoke')->once()->with('secret-refresh');
        $this->app->instance(OAuthClient::class, $client);

        $connection = $this->connection('9130');

        $this->delete(route('qbo-auth.connections.destroy', $connection))
            ->assertRedirect(route('qbo-auth.index'))
            ->assertHeader('Location', '/oauth/quickbooks')
            ->assertSessionHas('qbo-auth.status', ['succeeded' => true, 'message' => 'QuickBooks company 9130 disconnected.']);

        $this->assertModelMissing($connection);
    }

    public function test_destroy_deletes_the_connection_when_intuit_refuses_revocation(): void
    {
        $client = Mockery::mock(OAuthClient::class);
        $client->shouldReceive('revoke')->once()->andThrow(new ServiceException('Revoke Token failed.', 400));
        $this->app->instance(OAuthClient::class, $client);

        $connection = $this->connection('9130');

        $this->delete(route('qbo-auth.connections.destroy', $connection))
            ->assertRedirect(route('qbo-auth.index'));

        $this->assertModelMissing($connection);
        $this->assertFalse(session('qbo-auth.status')['succeeded']);
    }

    public function test_destroy_ignores_connections_from_another_environment(): void
    {
        $client = Mockery::mock(OAuthClient::class);
        $client->shouldNotReceive('revoke');
        $this->app->instance(OAuthClient::class, $client);

        $connection = $this->connection('9130', 'production');

        $this->delete(route('qbo-auth.connections.destroy', $connection))->assertNotFound();

        $this->assertModelExists($connection);
    }

    public function test_index_shows_refresh_button_for_each_connection(): void
    {
        config()->set('qbo-auth.redirect_uri', 'http://localhost/oauth/quickbooks/callback');

        $connection = $this->connection('9130');

        $this->get(route('qbo-auth.index'))
            ->assertOk()
            ->assertSee(route('qbo-auth.connections.refresh', $connection, false))
            ->assertSee('Refresh tokens for company 9130');
    }

    public function test_refresh_saves_new_tokens_and_expiry_dates(): void
    {
        $client = Mockery::mock(OAuthClient::class);
        $client->shouldReceive('refresh')->once()->with('secret-refresh')->andReturn($this->token('new-access', 'new-refresh'));
        $this->app->instance(OAuthClient::class, $client);

        $connection = $this->connection('9130');

        $this->post(route('qbo-auth.connections.refresh', $connection))
            ->assertRedirect(route('qbo-auth.index'))
            ->assertHeader('Location', '/oauth/quickbooks')
            ->assertSessionHas('qbo-auth.status', ['succeeded' => true, 'message' => 'QuickBooks company 9130 tokens refreshed.']);

        $connection->refresh();
        $this->assertSame('new-access', $connection->access_token);
        $this->assertSame('new-refresh', $connection->refresh_token);
        $this->assertNotNull($connection->access_token_expires_at);
        $this->assertNotNull($connection->refresh_token_expires_at);
    }

    public function test_refresh_reports_when_intuit_refuses_the_refresh_token(): void
    {
        $client = Mockery::mock(OAuthClient::class);
        $client->shouldReceive('refresh')->once()->andThrow(new ServiceException('Refresh OAuth 2 Access token with Refresh Token failed.', 400));
        $this->app->instance(OAuthClient::class, $client);

        $connection = $this->connection('9130');

        $this->post(route('qbo-auth.connections.refresh', $connection))
            ->assertRedirect(route('qbo-auth.index'));

        $this->assertFalse(session('qbo-auth.status')['succeeded']);
        $this->assertSame('secret-refresh', $connection->fresh()->refresh_token);
    }

    public function test_refresh_ignores_connections_from_another_environment(): void
    {
        $client = Mockery::mock(OAuthClient::class);
        $client->shouldNotReceive('refresh');
        $this->app->instance(OAuthClient::class, $client);

        $connection = $this->connection('9130', 'production');

        $this->post(route('qbo-auth.connections.refresh', $connection))->assertNotFound();
    }

    public function test_company_info_uses_the_stored_access_token_when_it_is_still_valid(): void
    {
        $client = Mockery::mock(OAuthClient::class);
        $client->shouldNotReceive('refresh');
        $this->app->instance(OAuthClient::class, $client);

        $companyInfo = Mockery::mock(CompanyInfoClient::class);
        $companyInfo->shouldReceive('fetch')->once()->with('secret-access', '9130')->andReturn($this->companyDetails());
        $this->app->instance(CompanyInfoClient::class, $companyInfo);

        $connection = $this->connection('9130');
        $connection->update(['access_token_expires_at' => now()->addHour()]);

        $this->getJson(route('qbo-auth.connections.company', $connection))
            ->assertOk()
            ->assertJsonPath('company.name', 'Sandbox Company')
            ->assertJsonPath('token_refreshed', false)
            ->assertDontSee('secret-');
    }

    public function test_company_info_refreshes_an_expired_access_token_first(): void
    {
        $client = Mockery::mock(OAuthClient::class);
        $client->shouldReceive('refresh')->once()->with('secret-refresh')->andReturn($this->token('new-access', 'new-refresh'));
        $this->app->instance(OAuthClient::class, $client);

        $companyInfo = Mockery::mock(CompanyInfoClient::class);
        $companyInfo->shouldReceive('fetch')->once()->with('new-access', '9130')->andReturn($this->companyDetails());
        $this->app->instance(CompanyInfoClient::class, $companyInfo);

        $connection = $this->connection('9130');
        $connection->update(['access_token_expires_at' => now()->subMinute()]);

        $this->getJson(route('qbo-auth.connections.company', $connection))
            ->assertOk()
            ->assertJsonPath('token_refreshed', true)
            ->assertDontSee('new-access');

        $this->assertSame('new-refresh', $connection->fresh()->refresh_token);
    }

    public function test_company_info_reports_quickbooks_errors_without_details(): void
    {
        $companyInfo = Mockery::mock(CompanyInfoClient::class);
        $companyInfo->shouldReceive('fetch')->once()->andThrow(new RuntimeException('QuickBooks returned an error (HTTP 401) while loading company info.'));
        $this->app->instance(CompanyInfoClient::class, $companyInfo);

        $connection = $this->connection('9130');
        $connection->update(['access_token_expires_at' => now()->addHour()]);

        $this->getJson(route('qbo-auth.connections.company', $connection))
            ->assertStatus(502)
            ->assertJsonPath('message', 'QuickBooks could not load this company. The connection may need to be reconnected.');
    }

    public function test_company_info_ignores_connections_from_another_environment(): void
    {
        $companyInfo = Mockery::mock(CompanyInfoClient::class);
        $companyInfo->shouldNotReceive('fetch');
        $this->app->instance(CompanyInfoClient::class, $companyInfo);

        $this->getJson(route('qbo-auth.connections.company', $this->connection('9130', 'production')))->assertNotFound();
    }

    public function test_refresh_saves_the_rotated_refresh_token(): void
    {
        $client = Mockery::mock(OAuthClient::class);
        $client->shouldReceive('refresh')->once()->with('old-refresh')->andReturn($this->token('new-access', 'new-refresh'));
        $this->app->instance(OAuthClient::class, $client);

        $connection = QuickBooksConnection::query()->create([
            'realm_id' => '123',
            'environment' => 'sandbox',
            'access_token' => 'old-access',
            'refresh_token' => 'old-refresh',
        ]);

        $updated = $this->app->make(QuickBooksConnections::class)->refresh($connection);

        $this->assertSame('new-access', $updated->access_token);
        $this->assertSame('new-refresh', $updated->refresh_token);
        $this->assertSame(1, QuickBooksConnection::query()->count());
    }

    private function connection(string $realmId, string $environment = 'sandbox'): QuickBooksConnection
    {
        return QuickBooksConnection::query()->create([
            'realm_id' => $realmId,
            'environment' => $environment,
            'access_token' => 'secret-access',
            'refresh_token' => 'secret-refresh',
        ]);
    }

    /** @return array<string, ?string> */
    private function companyDetails(): array
    {
        return [
            'name' => 'Sandbox Company',
            'legal_name' => 'Sandbox Company LLC',
            'email' => 'owner@example.com',
            'phone' => '555-0100',
            'website' => null,
            'address' => '123 Sierra Way, San Pablo, CA, 87999',
            'country' => 'US',
            'start_date' => '2026-10-02',
            'fiscal_year_start_month' => 'January',
            'created_at' => '2026-10-02T12:00:00-07:00',
        ];
    }

    private function token(string $accessToken, string $refreshToken): OAuth2AccessToken
    {
        $token = Mockery::mock(OAuth2AccessToken::class);
        $token->shouldReceive('getAccessToken')->andReturn($accessToken);
        $token->shouldReceive('getRefreshToken')->andReturn($refreshToken);
        $token->shouldReceive('getAccessTokenExpiresAt')->andReturn('2026/10/06 12:00:00');
        $token->shouldReceive('getRefreshTokenExpiresAt')->andReturn('2027/01/14 12:00:00');

        return $token;
    }
}
