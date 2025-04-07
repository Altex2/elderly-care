<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <title>{{ config('app.name', 'Reminder Buddy') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    <style>
        :root {
            /* Base Colors */
            --color-primary: #0056b3;
            --color-primary-hover: #004494;
            --color-secondary: #28a745;
            --color-danger: #dc3545;
            --color-warning: #ffc107;
            --color-info: #17a2b8;
            
            /* Text Colors */
            --color-text: #212529;
            --color-text-light: #6c757d;
            --color-text-white: #ffffff;
            
            /* Background Colors */
            --color-bg-primary: #ffffff;
            --color-bg-secondary: #f8f9fa;
            --color-bg-tertiary: #e9ecef;
            
            /* Spacing */
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            
            /* Typography */
            --font-size-xs: 0.875rem;
            --font-size-sm: 1rem;
            --font-size-md: 1.25rem;
            --font-size-lg: 1.5rem;
            --font-size-xl: 2rem;
            
            --line-height-base: 1.5;
            --border-radius: 0.5rem;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            font-size: var(--font-size-sm);
            line-height: var(--line-height-base);
            color: var(--color-text);
            background-color: var(--color-bg-secondary);
        }

        /* Container */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-sm);
        }

        /* Cards */
        .card {
            background-color: var(--color-bg-primary);
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-sm) var(--spacing-md);
            font-size: var(--font-size-sm);
            font-weight: 600;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.2s;
            min-height: 48px;
            min-width: 120px;
        }

        .btn-primary {
            background-color: var(--color-primary);
            color: var(--color-text-white);
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--color-primary-hover);
        }

        /* Form Controls */
        input, select, textarea {
            width: 100%;
            padding: var(--spacing-sm);
            font-size: var(--font-size-sm);
            border: 2px solid var(--color-bg-tertiary);
            border-radius: var(--border-radius);
            background-color: var(--color-bg-primary);
            color: var(--color-text);
            min-height: 48px;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        /* Links */
        a {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 600;
        }

        a:hover {
            text-decoration: underline;
        }

        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            color: var(--color-text);
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
        }

        h1 { font-size: var(--font-size-xl); }
        h2 { font-size: var(--font-size-lg); }
        h3 { font-size: var(--font-size-md); }
        h4 { font-size: var(--font-size-sm); }

        p {
            margin-bottom: var(--spacing-sm);
            line-height: var(--line-height-base);
        }
    </style>
</head>
<body class="h-full flex flex-col justify-center items-center py-12">
    <div class="flex flex-col sm:justify-center items-center">
        <div class="w-full sm:max-w-md px-6 py-4">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-primary">Reminder Buddy</h1>
            </div>

            <div class="card">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html> 