<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LiveTable – Demo Tailwind</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
</head>
<body class="bg-gray-50">

<div class="container mx-auto px-4 py-6">
    <div class="flex items-center gap-4 mb-6">
        <h4 class="text-xl font-semibold">LiveTable – Tailwind</h4>
        <a href="http://localhost:8002/demo" class="text-sm text-gray-500 hover:text-gray-800 underline">→ Bootstrap (port 8002)</a>
    </div>

    @livewire('demo-users-table')
</div>

@livewireScripts
</body>
</html>