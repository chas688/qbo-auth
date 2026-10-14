<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QuickBooks Connection</title>
    <style>
        body { margin: 0; font-family: system-ui, sans-serif; background: #f6f7f9; color: #1f2328; }
        main { max-width: 32rem; margin: 3rem auto; padding: 0 1rem; text-align: center; }
    </style>
</head>
<body>
<main>
    <p role="status">{{ $message }}</p>
    <p>You can close this window and <a href="{{ route('qbo-auth.index', [], false) }}">return to the QuickBooks connection page</a>.</p>
</main>

<script>
    (() => {
        const result = @json(['type' => 'qbo-auth', 'succeeded' => $succeeded, 'message' => $message]);

        if ('BroadcastChannel' in window) {
            const channel = new BroadcastChannel('qbo-auth');
            channel.postMessage(result);
            channel.close();
        }

        if (window.opener) {
            window.opener.postMessage(result, window.location.origin);
        }

        window.close();
    })();
</script>
</body>
</html>
