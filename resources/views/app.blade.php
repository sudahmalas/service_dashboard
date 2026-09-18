<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ID-Grow WebHost | Central Relay & Queue Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    @php
        $reverbScheme = env('REVERB_SCHEME');
        $isLocal = in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1']);

        // Jika REVERB_PORT diset manual di .env, kirimkan nilainya.
        // Jika kosong:
        // - Pada localhost: gunakan REVERB_SERVER_PORT (8090)
        // - Pada hosting: kirim null agar frontend otomatis memakai port 443 (HTTPS) atau 80 (HTTP)
        $explicitPort = env('REVERB_PORT') ? (int) env('REVERB_PORT') : null;
        $clientPort = $explicitPort ?: ($isLocal ? (int) (env('REVERB_SERVER_PORT') ?: 8090) : null);
    @endphp
    <script>
        window.__CONFIG__ = {
            reverb: {
                appKey: @json(env('REVERB_APP_KEY', 'za2zx1fb5ugbw2kcdtyb')),
                host: @json(env('REVERB_HOST') ?: null),
                port: @json($clientPort),
                scheme: @json($reverbScheme ?: null)
            },
            appName: @json(config('app.name', 'ID-Grow WebHost')),
            appUrl: @json(config('app.url', ''))
        };
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 antialiased selection:bg-indigo-500 selection:text-white min-h-screen">
    <div id="app"></div>
</body>
</html>
