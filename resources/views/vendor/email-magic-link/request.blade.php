<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta http-equiv="refresh" content="0; url={{ route('login') }}">
  <title>Redirect</title>
</head>
<body>
  <script>window.location.replace(@json(route('login')));</script>
  <p>Mengalihkan ke halaman login...</p>
</body>
</html>
