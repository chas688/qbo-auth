<?php

namespace CharlesHarmon\QboAuth;

use Carbon\CarbonImmutable;
use CharlesHarmon\QboAuth\Models\QuickBooksConnection;
use Illuminate\Database\Eloquent\Collection;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2AccessToken;
use QuickBooksOnline\API\Exception\SdkException;
use QuickBooksOnline\API\Exception\ServiceException;

class QuickBooksConnections
{
    public function __construct(private OAuthClient $client) {}

    public function find(string $realmId): ?QuickBooksConnection
    {
        return QuickBooksConnection::query()
            ->where('realm_id', $realmId)
            ->where('environment', $this->environment())
            ->first();
    }

    /** @return Collection<int, QuickBooksConnection> */
    public function all(): Collection
    {
        return QuickBooksConnection::query()
            ->where('environment', $this->environment())
            ->latest('updated_at')
            ->get();
    }

    public function save(string $realmId, OAuth2AccessToken $token): QuickBooksConnection
    {
        return QuickBooksConnection::query()->updateOrCreate(
            ['realm_id' => $realmId, 'environment' => $this->environment()],
            [
                'access_token' => $token->getAccessToken(),
                'refresh_token' => $token->getRefreshToken(),
                'access_token_expires_at' => $this->expiration($token->getAccessTokenExpiresAt()),
                'refresh_token_expires_at' => $this->expiration($token->getRefreshTokenExpiresAt()),
            ],
        );
    }

    public function refresh(QuickBooksConnection $connection): QuickBooksConnection
    {
        $token = $this->client->refresh($connection->refresh_token);

        return $this->save($connection->realm_id, $token);
    }

    /**
     * Refresh the access token when it has expired or will expire within a minute.
     */
    public function ensureFreshAccessToken(QuickBooksConnection $connection): QuickBooksConnection
    {
        $expiresAt = $connection->access_token_expires_at;

        if ($expiresAt !== null && $expiresAt->isAfter(now()->addMinute())) {
            return $connection;
        }

        return $this->refresh($connection);
    }

    /**
     * Revoke the connection's tokens at Intuit, then delete it. Returns false when Intuit refused the revocation; the connection is deleted either way.
     */
    public function disconnect(QuickBooksConnection $connection): bool
    {
        try {
            $this->client->revoke($connection->refresh_token);
            $revoked = true;
        } catch (SdkException|ServiceException $exception) {
            report($exception);
            $revoked = false;
        }

        $connection->delete();

        return $revoked;
    }

    private function environment(): string
    {
        return config('qbo-auth.environment', 'sandbox');
    }

    private function expiration(string $value): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y/m/d H:i:s', $value, date_default_timezone_get())->utc();
    }
}
