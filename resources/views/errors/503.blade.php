<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Sedang Maintenance — {{ config('app.name') }}</title>
    <style>
        :root {
            color-scheme: light dark;
            --bg: #f2f4f7;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --accent: #570df8;
            --border: #e5e7eb;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #111318;
                --card: #1b1f27;
                --text: #f3f4f6;
                --muted: #9ca3af;
                --border: #2b303b;
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
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
        }

        main {
            width: min(32rem, 100%);
            padding: 2rem;
            border: 1px solid var(--border);
            border-radius: 1rem;
            background: var(--card);
            box-shadow: 0 12px 32px rgb(0 0 0 / 0.08);
            text-align: center;
        }

        .brand {
            margin: 0 0 0.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--accent);
        }

        h1 {
            margin: 0 0 0.75rem;
            font-size: 1.5rem;
        }

        p {
            margin: 0;
            color: var(--muted);
            white-space: pre-line;
        }

        .hint {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
            font-size: 0.8125rem;
        }
    </style>
</head>
<body>
    <main>
        <p class="brand">{{ config('app.name') }}</p>
        <h1>Sedang dalam perbaikan</h1>
        <p>{{ $message ?? 'Aplikasi sedang dalam perbaikan. Silakan coba lagi nanti.' }}</p>
        <p class="hint">Halaman ini akan tersedia kembali setelah admin menyelesaikan perbaikan.</p>
    </main>
</body>
</html>
