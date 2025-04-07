<!DOCTYPE html>
<html data-bs-theme="light">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        
        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- App favicon -->
        <link rel="shortcut icon" href="{{ asset('zendash/img/favicon.ico') }}">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Righteous&display=swap" rel="stylesheet">


        <link href="{{ asset('zendash/plugins/bootstrap/css/bootstrap.css') }}" rel="stylesheet" />

        {{-- Icons --}}
        <link href="{{ asset('zendash/icons/materialdesignicons/materialdesignicons.css') }}" rel="stylesheet" />

        {{-- Stylings --}}
        <link href="{{ asset('zendash/css/style.css') }}" rel="stylesheet" />
        <link href="{{ asset('zendash/css/dark.css') }}" rel="stylesheet" />
        <link href="{{ asset('zendash/css/skins.css') }}" rel="stylesheet" />
        <link href="{{ asset('zendash/css/animated.css') }}" rel="stylesheet" />
        <link href="{{ asset('zendash/css/sidemenu.css') }}" rel="stylesheet" />
        <link href="{{ asset('zendash/css/style.css') }}" rel="stylesheet" />
        <link href="{{ asset('zendash/css/icons.css') }}" rel="stylesheet" />
        <link href="{{ asset('css/custom.css') }}" rel="stylesheet" />

        @vite(['resources/js/app.js', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
        @routes
        
    </head>
    <body class="app sidebar-mini">
        @inertia 
    </body>
</html>
