<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LiveTable – Demo Bootstrap</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center gap-3 mb-4">
        <h4 class="mb-0">LiveTable – Bootstrap</h4>
        <a href="http://localhost:8003/demo" class="btn btn-sm btn-outline-secondary">→ Tailwind (port 8003)</a>
    </div>

    @livewire('demo-users-table')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@livewireScripts
</body>
</html>