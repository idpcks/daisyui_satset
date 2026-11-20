<!doctype html>
<html lang="id" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="refresh" content="0; url={{ route('login') }}">
  <title>Mengalihkan...</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@5/dist/full.min.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-base-200 flex items-center justify-center p-4">
  <div class="card bg-base-100 shadow-xl w-full max-w-sm">
    <div class="card-body items-center text-center gap-4">
      <span class="loading loading-spinner loading-lg text-primary"></span>
      <h1 class="card-title">Mengalihkan ke halaman login</h1>
      <p class="text-base-content/70">Harap tunggu sebentar...</p>
      <a href="{{ route('login') }}" class="btn btn-primary btn-sm">Ke halaman login</a>
    </div>
  </div>

  <script>
    window.location.replace(@json(route('login')));
  </script>
</body>
</html>
