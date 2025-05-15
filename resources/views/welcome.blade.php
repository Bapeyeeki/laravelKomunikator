<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messenger Vue</title>

    <!-- Favicon & Fonts (opcjonalne) -->
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600&display=swap" rel="stylesheet">

    <!-- Includuj styl Vue (jeśli generujesz go Vite) -->
    @vite('resources/js/app.js') {{-- lub 'resources/js/main.js' zależnie od konfiguracji --}}
</head>
<body>
    <div id="app"></div> {{-- Tu Vue osadzi aplikację --}}

    {{-- Można dodać preloader, jeśli potrzebujesz --}}
</body>
</html>
