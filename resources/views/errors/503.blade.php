@php
    $appName = config('app.name', 'Aplikasi');
    $retryMessage = null;

    if (isset($retryAfter) && $retryAfter !== null && $retryAfter !== '') {
        if (is_numeric($retryAfter)) {
            $minutes = max(1, (int) ceil((int) $retryAfter / 60));
            $retryMessage = "Perkiraan selesai dalam {$minutes} menit.";
        } else {
            $retryMessage = "Perkiraan selesai: {$retryAfter}.";
        }
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Sedang Dalam Perbaikan &middot; {{ $appName }}</title>
    <style>
        :root {
            color-scheme: light dark;
            --bg: #f1f5f9;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --accent: #2563eb;
            --accent-soft: #dbeafe;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0f172a;
                --card: #1e293b;
                --text: #f8fafc;
                --muted: #94a3b8;
                --border: #334155;
                --accent: #60a5fa;
                --accent-soft: #1e3a8a;
            }
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            background: var(--bg);
            color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
        }

        .card {
            width: 100%;
            max-width: 34rem;
            padding: 2.5rem 2rem;
            border: 1px solid var(--border);
            border-radius: 1rem;
            background: var(--card);
            text-align: center;
            box-shadow: 0 20px 40px -24px rgb(15 23 42 / 0.35);
        }

        .mark {
            display: inline-grid;
            place-items: center;
            width: 3.5rem;
            height: 3.5rem;
            margin-bottom: 1.25rem;
            border-radius: 9999px;
            background: var(--accent-soft);
            color: var(--accent);
            font-size: 1.5rem;
            font-weight: 700;
        }

        .status {
            margin: 0 0 0.5rem;
            color: var(--accent);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0 0 0.75rem;
            font-size: 1.5rem;
            line-height: 1.3;
        }

        p {
            margin: 0;
            color: var(--muted);
            font-size: 0.95rem;
        }

        p + p {
            margin-top: 0.75rem;
        }

        .retry {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.5rem 0.9rem;
            border-radius: 9999px;
            background: var(--accent-soft);
            color: var(--accent);
            font-size: 0.85rem;
            font-weight: 600;
        }

        footer {
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="mark" aria-hidden="true">503</div>

        <p class="status">Maintenance</p>
        <h1>Aplikasi sedang dalam perbaikan</h1>
        <p>
            Kami sedang melakukan pemeliharaan terjadwal. Beberapa layanan mungkin
            sementara tidak dapat diakses.
        </p>
        <p>Silakan muat ulang halaman ini beberapa saat lagi.</p>

        @if ($retryMessage)
            <p class="retry">{{ $retryMessage }}</p>
        @endif

        <footer>{{ $appName }}</footer>
    </main>
</body>
</html>
