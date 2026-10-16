# Laravel QuickBooks OAuth2 package

This package wraps the official `quickbooks/v3-php-sdk` for the QuickBooks Online OAuth 2.0 connection flow. It is useful for testing and playing with how the oAuth flow will work with the SDK in a Laravel Application. It provides the authorization and callback routes, encrypted token storage, and a refresh method in a fairly simple way.

## Install in a Laravel application

Requires PHP 8.3+ and Laravel 12 or 13. Install it from [Packagist](https://packagist.org/packages/charlesharmon/qbo-auth):

```sh
composer require charlesharmon/qbo-auth --with-all-dependencies
```

`--with-all-dependencies` is needed in a new Laravel 13 application becausee Intuit's `quickbooks/v3-php-sdk` requires Guzzle 7, so Composer must be allowed to downgrade Guzzle. Laravel supports either version.

Composer discovers the service provider automatically. Run `php artisan migrate`. The package loads its own migration, so you do not need to publish it.

Set these values in the host application's environment:

```dotenv
QBO_ENVIRONMENT=sandbox
QBO_LOGGING=false
QBO_CLIENT_ID=<QBO_CLIENT_ID>
QBO_CLIENT_SECRET=<QBO_CLIENT_SECRET>
QBO_REDIRECT_URI=https://<NGROK URL>/oauth/quickbooks/callback
```

Register the exact redirect URI in the matching Intuit app. Sandbox credentials connect to sandbox companies; production credentials connect to production companies. Keep credentials and `APP_KEY` private and durable: Laravel encrypts stored tokens with `APP_KEY`.

## Connect and refresh

The examples below use `<ngrok_url>` for your public tunnel URL, such as `https://abc123.ngrok-free.app`. Without a tunnel, use the app's own URL, such as `http://localhost:8000`. All paths assume the default `oauth/quickbooks` prefix.

### Before you connect

1. Set `QBO_REDIRECT_URI=<ngrok_url>/oauth/quickbooks/callback` in `.env` and run `php artisan config:clear`.
2. Add the same URL, `<ngrok_url>/oauth/quickbooks/callback`, to your Intuit app under **Settings → Redirect URIs**. It must match exactly, and it must be registered for the same environment (sandbox or production) as your `QBO_CLIENT_ID`.

### Connect a Company & Test

1. Open `<ngrok_url>/oauth/quickbooks`. The page lists the connected companies for the current `QBO_ENVIRONMENT` and shows a **Connect to QuickBooks** button.
2. Click the button. It opens `<ngrok_url>/oauth/quickbooks/connect?popup=1` in a popup. The package stores a random state value in the session and redirects the popup to Intuit.
3. Sign in to Intuit and choose a company. Intuit redirects to `<ngrok_url>/oauth/quickbooks/callback?code=…&state=…&realmId=…`.
4. The callback checks the state, exchanges the code for tokens with Intuit's SDK, and saves both tokens for the returned `realmId`. Connecting the same company again updates its saved tokens instead of adding a duplicate.
5. Click the company's Realm ID to test that the API is called and company information is returned.

You can also open `<ngrok_url>/oauth/quickbooks/connect` directly, without `popup=1`. The browser then goes through the same flow in the main window, and the callback returns JSON with the `realm_id` and `environment`. Tokens are never included in a response.

Open these pages on `<ngrok_url>`, not `localhost`. If `/oauth/quickbooks` or `/oauth/quickbooks/connect` is opened on another host, the package first redirects to the host in `QBO_REDIRECT_URI`. This keeps the session cookie that holds the state on the host Intuit returns to. If that tunnel is offline (for example, ngrok restarted with a new URL), you land on ngrok's offline page instead of the connect page. Update `QBO_REDIRECT_URI` and the Intuit redirect URI to the new URL.

### Refresh tokens

Intuit access tokens expire after about an hour. The refresh token is used to get a new access token, and each refresh can return a new refresh token. The package saves the access token, refresh token, and both expiry times together after every refresh.

- **From the connect page:** click a company ID. The modal loads the company's CompanyInfo from `GET <ngrok_url>/oauth/quickbooks/connections/{id}/company`, where `{id}` is the connection's database ID. That request refreshes the access token first if it has expired or expires within a minute. The JSON response includes `token_refreshed` and `access_token_expires_at`.
- **From your own code:** use `CharlesHarmon\QboAuth\QuickBooksConnections`. `ensureFreshAccessToken()` refreshes only when needed; `refresh()` always refreshes. Both return the updated connection, so use the returned model for the next API call:

  ```php
  $connections = app(\CharlesHarmon\QboAuth\QuickBooksConnections::class);
  $connection = $connections->find($realmId);
  $connection = $connections->ensureFreshAccessToken($connection);
  // $connection->access_token is now valid for the QuickBooks API.
  ```

The package does not schedule refreshes. If a refresh token expires or is revoked, connect the company again.

### Disconnect a company

On the connect page, click the remove button next to a company and confirm. This sends `DELETE <ngrok_url>/oauth/quickbooks/connections/{id}`, which revokes the refresh token at Intuit and deletes the saved connection. The connection is deleted even if Intuit refuses the revocation; the page then tells you to revoke access from the QuickBooks app settings.

### Logging

Set `QBO_LOGGING=true` to write OAuth flow events to Laravel's configured log, usually `storage/logs/laravel.log`. These events include host and session-presence diagnostics, but never authorization codes, client secrets, state values, or tokens. The SDK's raw request logger is intentionally left off because it logs sensitive OAuth request data.



