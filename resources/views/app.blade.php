<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Invoices | Laravel</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons\favicon-32x32.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    
    <script defer src="{{ asset('js/theme.js') }}"></script>
    <script defer src="{{ asset('js/background.js') }}"></script>
</head>

<body>
    <header>
        <app-logo>
            <app-logo-inset>
                <img src="{{ asset('icons/logo.svg') }}">
                </img>
            </app-logo-inset>
        </app-logo>
        <header-spacer></header-spacer>
        <light-theme-toggle>
            <img src="{{ asset('icons/icon-sun.svg') }}" onClick="setLightTheme()">
            </img>
        </light-theme-toggle>
        <dark-theme-toggle>
            <img src="{{ asset('icons/icon-moon.svg') }}" onClick="setDarkTheme()">
            </img>
        </dark-theme-toggle>
        <profile-icon>
            <img src="{{ asset('icons/image-avatar.jpg') }}">
            </img>
        </profile-icon>
    </header>
    <invoices-list>
    </invoices-list>
    <invoice-view>
    </invoice-view>
    <invoice-edit>
    </invoice-edit>
</body>

</html>
