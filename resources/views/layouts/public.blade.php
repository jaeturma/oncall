<!DOCTYPE html>
<html lang="en-PH">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#01273a">
    <title>{{ isset($title) ? $title.' · Oncall Philippines' : 'Oncall Philippines — Find trusted help near you' }}</title>
    <meta name="description" content="{{ $description ?? 'Connect with verified local service providers across the Philippines for urgent, household, skilled, and professional services. Keep every agreement on Oncall.' }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    {{ Vite::fonts() }}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-canvas text-ink">
    <a class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-navy-900 focus:px-4 focus:py-2 focus:text-white" href="#main">Skip to content</a>
    <x-partials.header />
    <main id="main" class="flex-1">{{ $slot }}</main>
    <x-partials.footer />
</body>
</html>
