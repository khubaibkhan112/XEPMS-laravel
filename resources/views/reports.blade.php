<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reports - {{ config('app.name', 'XEPMS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="reports-app"></div>
    <script>
        window.toastr = window.toastr || {
            success: function(msg) { alert(msg); },
            error: function(msg) { alert(msg); },
            info: function(msg) { alert(msg); },
            warning: function(msg) { alert(msg); }
        };
    </script>
</body>
</html>

