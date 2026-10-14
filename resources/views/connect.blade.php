<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QuickBooks Connection</title>
    <script>
        // Apply a saved theme before first paint so the page never flashes the wrong colors.
        try {
            const savedTheme = localStorage.getItem('qbo-auth-theme');

            if (savedTheme === 'light' || savedTheme === 'dark') {
                document.documentElement.dataset.theme = savedTheme;
            }
        } catch (error) {}
    </script>
    <style>
        :root {
            color-scheme: light;
            --page-bg: #e8ecf1;
            --page-bg-top: #e4e9ef;
            --page-bg-bottom: #eef1f5;
            --page-glow: rgba(44, 160, 28, 0.12);
            --text: #1f2328;
            --text-muted: #59636e;
            --surface: #fff;
            --surface-subtle: #f6f8fa;
            --surface-hover: #f3f4f6;
            --empty-bg: rgba(255, 255, 255, 0.7);
            --border: #cfd7df;
            --border-muted: #eaeef2;
            --border-dashed: #b9c3cd;
            --link: #0969da;
            --link-underline: rgba(9, 105, 218, 0.35);
            --success-bg: #dafbe1;
            --success-text: #116329;
            --warning-bg: #fff8c5;
            --warning-text: #7d4e00;
            --error-bg: #ffebe9;
            --error-text: #82071e;
            --info-bg: #ddf4ff;
            --info-text: #0a3069;
            --danger: #cf222e;
            --danger-hover: #a40e26;
            --danger-icon: #cf222e;
            --overlay-hover: rgba(31, 35, 40, 0.08);
            --spinner-track: #d1d9e0;
            --shadow-card: 0 1px 2px rgba(31, 35, 40, 0.06), 0 8px 24px rgba(31, 35, 40, 0.08);
            --shadow-modal: 0 12px 32px rgba(31, 35, 40, 0.24);
            --backdrop: rgba(31, 35, 40, 0.45);
        }
        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                color-scheme: dark;
                --page-bg: #0f141a;
                --page-bg-top: #0b0f14;
                --page-bg-bottom: #121820;
                --page-glow: rgba(46, 160, 67, 0.14);
                --text: #e6edf3;
                --text-muted: #9198a1;
                --surface: #151b23;
                --surface-subtle: #1c232c;
                --surface-hover: #262c36;
                --empty-bg: rgba(21, 27, 35, 0.7);
                --border: #3d444d;
                --border-muted: #262c36;
                --border-dashed: #3d444d;
                --link: #4493f8;
                --link-underline: rgba(68, 147, 248, 0.4);
                --success-bg: rgba(46, 160, 67, 0.15);
                --success-text: #3fb950;
                --warning-bg: rgba(210, 153, 34, 0.15);
                --warning-text: #d29922;
                --error-bg: rgba(248, 81, 73, 0.15);
                --error-text: #ff7b72;
                --info-bg: rgba(56, 139, 253, 0.15);
                --info-text: #79c0ff;
                --danger: #da3633;
                --danger-hover: #b62324;
                --danger-icon: #ff7b72;
                --overlay-hover: rgba(230, 237, 243, 0.1);
                --spinner-track: #3d444d;
                --shadow-card: 0 1px 2px rgba(1, 4, 9, 0.4), 0 8px 24px rgba(1, 4, 9, 0.35);
                --shadow-modal: 0 12px 32px rgba(1, 4, 9, 0.6);
                --backdrop: rgba(1, 4, 9, 0.7);
            }
            :root:not([data-theme="light"]) .theme-icon-sun { display: block; }
            :root:not([data-theme="light"]) .theme-icon-moon { display: none; }
        }
        :root[data-theme="dark"] {
            color-scheme: dark;
            --page-bg: #0f141a;
            --page-bg-top: #0b0f14;
            --page-bg-bottom: #121820;
            --page-glow: rgba(46, 160, 67, 0.14);
            --text: #e6edf3;
            --text-muted: #9198a1;
            --surface: #151b23;
            --surface-subtle: #1c232c;
            --surface-hover: #262c36;
            --empty-bg: rgba(21, 27, 35, 0.7);
            --border: #3d444d;
            --border-muted: #262c36;
            --border-dashed: #3d444d;
            --link: #4493f8;
            --link-underline: rgba(68, 147, 248, 0.4);
            --success-bg: rgba(46, 160, 67, 0.15);
            --success-text: #3fb950;
            --warning-bg: rgba(210, 153, 34, 0.15);
            --warning-text: #d29922;
            --error-bg: rgba(248, 81, 73, 0.15);
            --error-text: #ff7b72;
            --info-bg: rgba(56, 139, 253, 0.15);
            --info-text: #79c0ff;
            --danger: #da3633;
            --danger-hover: #b62324;
            --danger-icon: #ff7b72;
            --overlay-hover: rgba(230, 237, 243, 0.1);
            --spinner-track: #3d444d;
            --shadow-card: 0 1px 2px rgba(1, 4, 9, 0.4), 0 8px 24px rgba(1, 4, 9, 0.35);
            --shadow-modal: 0 12px 32px rgba(1, 4, 9, 0.6);
            --backdrop: rgba(1, 4, 9, 0.7);
        }
        :root[data-theme="dark"] .theme-icon-sun { display: block; }
        :root[data-theme="dark"] .theme-icon-moon { display: none; }
        body { min-height: 100vh; margin: 0; font-family: system-ui, sans-serif; color: var(--text); background-color: var(--page-bg); background-image: radial-gradient(ellipse 70rem 26rem at 50% -6rem, var(--page-glow), transparent 70%), linear-gradient(180deg, var(--page-bg-top) 0%, var(--page-bg-bottom) 60%); background-attachment: fixed; }
        main { max-width: 60rem; margin: 3rem auto; padding: 0 1rem; }
        [hidden] { display: none !important; }
        .muted { color: var(--text-muted); }
        .page-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .page-header h1 { margin: 0.67em 0; }
        .theme-toggle { display: inline-flex; flex-shrink: 0; align-items: center; justify-content: center; width: 2.25rem; height: 2.25rem; padding: 0; border: 1px solid var(--border); border-radius: 999px; background: var(--surface); color: var(--text-muted); box-shadow: var(--shadow-card); cursor: pointer; }
        .theme-toggle:hover, .theme-toggle:focus-visible { background: var(--surface-hover); color: var(--text); }
        .theme-toggle svg { width: 1.125rem; height: 1.125rem; }
        .theme-icon-sun { display: none; }
        .status { margin: 1rem 0; padding: 0.75rem 1rem; border-radius: 0.375rem; overflow: hidden; transition: opacity 0.3s ease, height 0.3s ease, margin 0.3s ease, padding 0.3s ease; }
        .status-success { background: var(--success-bg); color: var(--success-text); }
        .status-error { background: var(--error-bg); color: var(--error-text); }
        .status-fading { opacity: 0; height: 0 !important; margin-block: 0; padding-block: 0; }
        .status-dismissible { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .status-close { flex-shrink: 0; width: 1.75rem; height: 1.75rem; padding: 0; border: 0; border-radius: 0.25rem; background: transparent; color: inherit; font-size: 1.25rem; line-height: 1; cursor: pointer; }
        .status-close:hover { background: var(--overlay-hover); }
        #connect-button { padding: 0.625rem 1.25rem; border: 0; border-radius: 0.375rem; background: #2ca01c; color: #fff; font-size: 1rem; cursor: pointer; }
        #connect-button:hover { background: #238016; }
        .section-heading { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; margin-top: 2.5rem; }
        .section-heading h2 { margin: 0; font-size: 1.125rem; }
        .table-card { margin-top: 0.75rem; overflow-x: auto; background: var(--surface); border: 1px solid var(--border); border-radius: 0.625rem; box-shadow: var(--shadow-card); }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th, td { padding: 0.75rem 1rem; text-align: left; white-space: nowrap; }
        th { background: var(--surface-subtle); color: var(--text-muted); font-size: 0.75rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; border-bottom: 1px solid var(--border); }
        tbody tr + tr td { border-top: 1px solid var(--border-muted); }
        tbody tr:hover { background: var(--surface-subtle); }
        .realm-id { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 600; }
        .link-button { padding: 0; border: 0; background: none; color: var(--link); font: inherit; cursor: pointer; text-decoration: underline; text-decoration-color: var(--link-underline); text-underline-offset: 0.2em; }
        .link-button:hover, .link-button:focus-visible { text-decoration-color: currentColor; }
        .modal-wide { width: min(34rem, calc(100% - 2rem)); }
        .modal-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-muted); }
        .modal-header h3 { margin: 0; }
        .modal-subtitle { margin-top: 0.25rem !important; font-size: 0.8125rem; }
        .modal-content { padding: 1.25rem 1.5rem; }
        .modal-close { flex-shrink: 0; width: 2rem; height: 2rem; padding: 0; border: 0; border-radius: 0.375rem; background: transparent; color: var(--text-muted); font-size: 1.375rem; line-height: 1; cursor: pointer; }
        .modal-close:hover { background: var(--surface-hover); color: var(--text); }
        .details { display: grid; grid-template-columns: max-content 1fr; gap: 0.625rem 1.25rem; margin: 0; font-size: 0.875rem; }
        .details dt { color: var(--text-muted); }
        .details dd { margin: 0; overflow-wrap: anywhere; }
        .modal .status-error { color: var(--error-text); }
        .modal .api-note { display: flex; align-items: center; gap: 0.5rem; margin-top: 1.25rem; padding: 0.625rem 0.75rem; border-radius: 0.375rem; background: var(--info-bg); color: var(--info-text); font-size: 0.8125rem; }
        .loading { display: flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 2rem 0; color: var(--text-muted); }
        .spinner { width: 1.25rem; height: 1.25rem; border: 2px solid var(--spinner-track); border-top-color: #2ca01c; border-radius: 999px; animation: spin 0.7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        @media (max-width: 30rem) { .details { grid-template-columns: 1fr; gap: 0.125rem; } .details dd { margin-bottom: 0.5rem; } }
        .date-detail { display: block; color: var(--text-muted); font-size: 0.75rem; }
        .badge { display: inline-block; padding: 0.125rem 0.5rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .badge-active { background: var(--success-bg); color: var(--success-text); }
        .badge-warning { background: var(--warning-bg); color: var(--warning-text); }
        .badge-expired { background: var(--error-bg); color: var(--error-text); }
        .empty-state { margin-top: 0.75rem; padding: 2rem 1rem; text-align: center; background: var(--empty-bg); border: 1px dashed var(--border-dashed); border-radius: 0.625rem; }
        .visually-hidden { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
        .actions { width: 1%; text-align: right; }
        .icon-button { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; padding: 0; border: 0; border-radius: 0.375rem; background: transparent; color: var(--text-muted); cursor: pointer; }
        .icon-button:hover, .icon-button:focus-visible { background: var(--error-bg); color: var(--danger-icon); }
        .icon-button svg { width: 1.125rem; height: 1.125rem; }
        .modal { width: min(26rem, calc(100% - 2rem)); padding: 0; border: 0; border-radius: 0.75rem; background: var(--surface); box-shadow: var(--shadow-modal); color: var(--text); }
        .modal::backdrop { background: var(--backdrop); }
        .modal[open] { animation: modal-in 0.18s ease-out; }
        .modal-body { display: flex; gap: 1rem; padding: 1.5rem 1.5rem 1rem; }
        .modal-icon { display: flex; flex-shrink: 0; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; border-radius: 999px; background: var(--error-bg); color: var(--danger-icon); }
        .modal-icon svg { width: 1.25rem; height: 1.25rem; }
        .modal h3 { margin: 0 0 0.375rem; font-size: 1.0625rem; }
        .modal p { margin: 0; color: var(--text-muted); line-height: 1.5; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 0.5rem; padding: 1rem 1.5rem; background: var(--surface-subtle); }
        .modal-actions button { padding: 0.5rem 1rem; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 600; cursor: pointer; }
        .button-secondary { border: 1px solid var(--border); background: var(--surface); color: var(--text); }
        .button-secondary:hover { background: var(--surface-hover); }
        .button-danger { border: 1px solid var(--danger); background: var(--danger); color: #fff; }
        .button-danger:hover { background: var(--danger-hover); }
        @keyframes modal-in { from { opacity: 0; transform: translateY(0.5rem) scale(0.98); } }
        @media (prefers-reduced-motion: reduce) { .status { transition: none; } .modal[open] { animation: none; } }
    </style>
</head>
<body>
<main>
    <header class="page-header">
        <h1>QuickBooks connection</h1>
        <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Switch to dark mode" title="Switch to dark mode">
            <svg class="theme-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
            <svg class="theme-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="4"/>
                <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
            </svg>
        </button>
    </header>
    <p class="muted">Environment: {{ $environment }}</p>

    @if (is_array($status))
        <div class="status status-dismissible {{ $status['succeeded'] ? 'status-success' : 'status-error' }}" role="status" @if ($status['succeeded']) data-dismiss-after="5000" @endif>
            <span>{{ $status['message'] }}</span>
            <button type="button" class="status-close" aria-label="Dismiss message">&times;</button>
        </div>
    @endif

    <p id="popup-status" class="status status-error" role="status" hidden></p>

    <button type="button" id="connect-button" data-connect-url="{{ route('qbo-auth.connect', ['popup' => 1], false) }}">
        Connect to QuickBooks
    </button>

    <div class="section-heading">
        <h2>Connected companies</h2>
        <span class="muted">{{ trans_choice('{0} None|{1} 1 company|[2,*] :count companies', $connections->count()) }}</span>
    </div>

    @if ($connections->isEmpty())
        <p class="empty-state muted">No companies are connected yet. Use the button above to connect one.</p>
    @else
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Realm ID</th>
                        <th>Status</th>
                        <th>Access token expires</th>
                        <th>Refresh token expires</th>
                        <th>Last connected</th>
                        <th class="actions"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($connections as $connection)
                        @php
                            $refreshExpired = $connection->refresh_token_expires_at?->isPast() ?? false;
                            $accessExpired = $connection->access_token_expires_at?->isPast() ?? false;
                        @endphp
                        <tr>
                            <td class="realm-id">
                                <button
                                    type="button"
                                    class="link-button"
                                    title="Load company info from QuickBooks"
                                    data-company-url="{{ route('qbo-auth.connections.company', $connection, false) }}"
                                    data-realm-id="{{ $connection->realm_id }}"
                                >{{ $connection->realm_id }}</button>
                            </td>
                            <td>
                                @if ($refreshExpired)
                                    <span class="badge badge-expired">Reconnect required</span>
                                @elseif ($accessExpired)
                                    <span class="badge badge-warning">Needs refresh</span>
                                @else
                                    <span class="badge badge-active">Active</span>
                                @endif
                            </td>
                            @foreach ([$connection->access_token_expires_at, $connection->refresh_token_expires_at, $connection->updated_at] as $date)
                                <td>
                                    @if ($date)
                                        {{ $date->diffForHumans() }}
                                        <span class="date-detail">{{ $date->format('M j, Y g:i A T') }}</span>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="actions">
                                <button
                                    type="button"
                                    class="icon-button"
                                    title="Remove connection"
                                    aria-label="Remove connection for company {{ $connection->realm_id }}"
                                    data-delete-url="{{ route('qbo-auth.connections.destroy', $connection, false) }}"
                                    data-realm-id="{{ $connection->realm_id }}"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6M10 11v6M14 11v6"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</main>

<dialog id="delete-modal" class="modal" aria-labelledby="delete-modal-title" aria-describedby="delete-modal-description">
    <form method="POST">
        @csrf
        @method('DELETE')
        <div class="modal-body">
            <div class="modal-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6M10 11v6M14 11v6"/>
                </svg>
            </div>
            <div>
                <h3 id="delete-modal-title">Remove this connection?</h3>
                <p id="delete-modal-description">
                    Company <strong data-modal-realm-id></strong> will be disconnected from QuickBooks and its saved tokens deleted. You will need to connect it again to use it.
                </p>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="button-secondary" data-modal-cancel autofocus>Cancel</button>
            <button type="submit" class="button-danger">Remove connection</button>
        </div>
    </form>
</dialog>

<dialog id="company-modal" class="modal modal-wide" aria-labelledby="company-modal-title">
    <div class="modal-header">
        <div>
            <h3 id="company-modal-title">Company info</h3>
            <p class="modal-subtitle">Company ID <span class="realm-id" data-company-realm-id></span></p>
        </div>
        <button type="button" class="modal-close" aria-label="Close" data-company-close>&times;</button>
    </div>
    <div class="modal-content">
        <div class="loading" data-company-loading>
            <span class="spinner" aria-hidden="true"></span>
            <span>Calling QuickBooks…</span>
        </div>
        <p class="status status-error" data-company-error hidden></p>
        <div data-company-details hidden>
            <dl class="details"></dl>
            <p class="api-note" data-company-note></p>
        </div>
    </div>
    <div class="modal-actions">
        <button type="button" class="button-secondary" data-company-close>Close</button>
    </div>
</dialog>

<script>
    (() => {
        const channelName = 'qbo-auth';
        const button = document.getElementById('connect-button');
        const popupStatus = document.getElementById('popup-status');
        let popup = null;
        let finished = false;

        const themeToggle = document.getElementById('theme-toggle');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)');
        const currentTheme = () => document.documentElement.dataset.theme || (systemPrefersDark.matches ? 'dark' : 'light');

        const updateThemeToggle = () => {
            const label = currentTheme() === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
            themeToggle.setAttribute('aria-label', label);
            themeToggle.title = label;
        };

        themeToggle.addEventListener('click', () => {
            const nextTheme = currentTheme() === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = nextTheme;

            try {
                localStorage.setItem('qbo-auth-theme', nextTheme);
            } catch (error) {}

            updateThemeToggle();
        });

        systemPrefersDark.addEventListener('change', updateThemeToggle);
        updateThemeToggle();

        // Pin the current height first so the browser can animate the collapse down to zero.
        const dismiss = (message) => {
            if (message.classList.contains('status-fading')) {
                return;
            }

            message.style.height = `${message.offsetHeight}px`;
            message.offsetHeight;
            message.classList.add('status-fading');
            setTimeout(() => message.remove(), 300);
        };

        document.querySelectorAll('.status-dismissible').forEach((message) => {
            message.querySelector('.status-close').addEventListener('click', () => dismiss(message));

            if (message.dataset.dismissAfter) {
                setTimeout(() => dismiss(message), Number(message.dataset.dismissAfter));
            }
        });

        const deleteModal = document.getElementById('delete-modal');
        const deleteForm = deleteModal.querySelector('form');

        document.querySelectorAll('[data-delete-url]').forEach((deleteButton) => {
            deleteButton.addEventListener('click', () => {
                deleteForm.action = deleteButton.dataset.deleteUrl;
                deleteModal.querySelector('[data-modal-realm-id]').textContent = deleteButton.dataset.realmId;
                deleteModal.showModal();
            });
        });

        deleteModal.querySelector('[data-modal-cancel]').addEventListener('click', () => deleteModal.close());

        // Clicking the backdrop lands on the dialog element itself, outside its content.
        deleteModal.addEventListener('click', (event) => {
            if (event.target === deleteModal) {
                deleteModal.close();
            }
        });

        deleteForm.addEventListener('submit', () => {
            deleteForm.querySelector('[type="submit"]').disabled = true;
        });

        const companyModal = document.getElementById('company-modal');
        const companyLoading = companyModal.querySelector('[data-company-loading]');
        const companyError = companyModal.querySelector('[data-company-error]');
        const companyDetails = companyModal.querySelector('[data-company-details]');
        const companyFields = [
            ['name', 'Company name'],
            ['legal_name', 'Legal name'],
            ['email', 'Email'],
            ['phone', 'Phone'],
            ['website', 'Website'],
            ['address', 'Address'],
            ['country', 'Country'],
            ['start_date', 'Company start date'],
            ['fiscal_year_start_month', 'Fiscal year starts'],
            ['created_at', 'Created in QuickBooks'],
        ];
        let companyRequest = null;
        let tokenWasRefreshed = false;

        const showCompanyState = (state) => {
            companyLoading.hidden = state !== 'loading';
            companyError.hidden = state !== 'error';
            companyDetails.hidden = state !== 'details';
        };

        const renderCompany = (data) => {
            const list = companyDetails.querySelector('dl');
            list.replaceChildren();

            companyFields.forEach(([key, label]) => {
                const term = document.createElement('dt');
                const value = document.createElement('dd');
                term.textContent = label;
                value.textContent = key === 'created_at' && data.company[key]
                    ? new Date(data.company[key]).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })
                    : data.company[key] || '—';
                list.append(term, value);
            });

            const expiresAt = data.access_token_expires_at ? new Date(data.access_token_expires_at).toLocaleString() : 'unknown';
            companyModal.querySelector('#company-modal-title').textContent = data.company.name || 'Company info';
            companyModal.querySelector('[data-company-note]').textContent = data.token_refreshed
                ? `The access token had expired, so it was refreshed before this call. The new token expires ${expiresAt}.`
                : `Loaded live from QuickBooks with the stored access token, which expires ${expiresAt}.`;
            tokenWasRefreshed = tokenWasRefreshed || data.token_refreshed;
            showCompanyState('details');
        };

        document.querySelectorAll('[data-company-url]').forEach((companyButton) => {
            companyButton.addEventListener('click', async () => {
                companyRequest?.abort();
                companyRequest = new AbortController();

                companyModal.querySelector('#company-modal-title').textContent = 'Company info';
                companyModal.querySelector('[data-company-realm-id]').textContent = companyButton.dataset.realmId;
                showCompanyState('loading');
                companyModal.showModal();

                try {
                    const response = await fetch(companyButton.dataset.companyUrl, {
                        headers: { Accept: 'application/json' },
                        signal: companyRequest.signal,
                    });
                    const data = await response.json().catch(() => ({}));

                    if (!response.ok || !data.company) {
                        throw new Error(data.message || 'QuickBooks could not load this company.');
                    }

                    renderCompany(data);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        companyError.textContent = error.message;
                        showCompanyState('error');
                    }
                }
            });
        });

        const closeCompanyModal = () => companyModal.close();

        companyModal.querySelectorAll('[data-company-close]').forEach((closeButton) => closeButton.addEventListener('click', closeCompanyModal));

        companyModal.addEventListener('click', (event) => {
            if (event.target === companyModal) {
                closeCompanyModal();
            }
        });

        // A refresh changed the token expiry dates, so reload to bring the table up to date.
        companyModal.addEventListener('close', () => {
            companyRequest?.abort();

            if (tokenWasRefreshed) {
                window.location.reload();
            }
        });

        // The callback flashes its result to the session, so reloading shows the message and the updated list.
        const finish = () => {
            if (!finished) {
                finished = true;
                window.location.reload();
            }
        };

        // BroadcastChannel still works when Intuit's pages sever window.opener; postMessage covers older browsers.
        if ('BroadcastChannel' in window) {
            new BroadcastChannel(channelName).addEventListener('message', finish);
        }

        window.addEventListener('message', (event) => {
            if (event.origin === window.location.origin && event.data?.type === channelName) {
                finish();
            }
        });

        button.addEventListener('click', () => {
            if (popup && !popup.closed) {
                popup.focus();

                return;
            }

            const width = 600;
            const height = 720;
            const left = Math.round(window.screenX + (window.outerWidth - width) / 2);
            const top = Math.round(window.screenY + (window.outerHeight - height) / 2);

            popup = window.open(
                button.dataset.connectUrl,
                'qbo-auth-popup',
                `popup,width=${width},height=${height},left=${left},top=${top}`,
            );

            popupStatus.hidden = popup !== null;
            popupStatus.textContent = 'Your browser blocked the QuickBooks window. Allow popups for this site and try again.';
        });
    })();
</script>
</body>
</html>
