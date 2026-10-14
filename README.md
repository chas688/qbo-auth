# Laravel QuickBooks OAuth2 package

This package wraps the official `quickbooks/v3-php-sdk` for the QuickBooks Online OAuth 2.0 connection flow. It provides the authorization and callback routes, encrypted token storage, and a refresh method. The application supplies its own Intuit credentials, session storage, database, and access control.

## Install in a Laravel application

For local development, add a Composer path repository that points to this package and require `charlesharmon/qbo-auth:dev-main`:

```json
"repositories": [{"type": "path", "url": "libraries/qboAuth", "options": {"symlink": true}}],
"require": {"charlesharmon/qbo-auth": "dev-main"}
```

After moving this directory into its own Git repository, replace the path repository with its Git URL, for example:

```sh
composer config repositories.qbo-auth vcs https://github.com/YOUR-ACCOUNT/YOUR-REPO.git
composer require charlesharmon/qbo-auth:dev-main
```

Use a tagged version constraint after the first release. Composer discovers the service provider automatically. Run `php artisan migrate` to create `quick_books_connections`. The package includes its migration, so do not copy it into the host app.

Set these values in the host application's environment:

```dotenv
QBO_ENVIRONMENT=sandbox
QBO_LOGGING=false
QBO_CLIENT_ID=
QBO_CLIENT_SECRET=
QBO_REDIRECT_URI=http://localhost:8000/oauth/quickbooks/callback
```

Register the exact redirect URI in the matching Intuit app. Sandbox credentials connect to sandbox companies; production credentials connect to production companies. Keep credentials and `APP_KEY` private and durable: Laravel encrypts stored tokens with `APP_KEY`.

## Connect and refresh

Visit the `qbo-auth.connect` route to begin authorization. The package generates a session state, validates it on `qbo-auth.callback`, exchanges the code using Intuit's SDK, and saves both tokens for the returned `realmId`. The callback responds with the company ID and environment, without exposing tokens. The default paths are `/oauth/quickbooks/connect` and `/oauth/quickbooks/callback`.

For a popup flow, visit the `qbo-auth.index` route (`/oauth/quickbooks`). Its button opens `connect?popup=1` in a popup window. In popup mode, the callback flashes the result to the session, notifies the opening page (via `BroadcastChannel` and same-origin `postMessage`), and closes itself. The opening page then reloads to show the result and the connected companies. Without `popup=1`, the callback still returns JSON. Clicking a company ID opens a modal that loads the company's CompanyInfo from QuickBooks through `GET /oauth/quickbooks/connections/{id}/company` (`qbo-auth.connections.company`). That route first refreshes the access token if it has expired or expires within a minute. Each listed company has a remove button. After confirmation, it sends `DELETE /oauth/quickbooks/connections/{id}` (`qbo-auth.connections.destroy`). That route revokes the refresh token at Intuit and deletes the saved connection, and it still deletes the connection if Intuit refuses the revocation.

When using a tunnel such as ngrok, set `QBO_REDIRECT_URI` to its HTTPS callback URL and open the connect route on that same tunnel host. If the connect route is opened on another host, the package redirects to the configured callback host before creating the session state. This keeps the browser's session cookie on the host that receives the callback.

Set `QBO_LOGGING=true` to write OAuth flow events to Laravel's configured log, usually `storage/logs/laravel.log`. These events include host and session-presence diagnostics, but never authorization codes, client secrets, state values, or tokens. The SDK's raw request logger is intentionally left off because it logs sensitive OAuth request data.

The routes use Laravel's `web` middleware by default. For an application with users, publish the config and add your authorization middleware to `route_middleware` before exposing the connect route:

```sh
php artisan vendor:publish --tag=qbo-auth-config
```

Use `CharlesHarmon\QboAuth\QuickBooksConnections` to look up and refresh a connection:

```php
$connections = app(\CharlesHarmon\QboAuth\QuickBooksConnections::class);
$connection = $connections->find($realmId);
$connection = $connections->refresh($connection);
```

`refresh()` persists the newly returned access and refresh tokens together. Call it before the access token expires and retain the refreshed token for future requests. The package currently handles authorization and token persistence only; it does not make accounting API requests or run a refresh schedule.
