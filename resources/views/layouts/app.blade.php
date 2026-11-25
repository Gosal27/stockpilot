<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'StockPilot')</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    :root {
        --bg-main: #09090b;
        --bg-sidebar: #101012;
        --bg-card: #171719;
        --bg-card-hover: #202024;
        --border-color: #303034;

        --text-primary: #f4f4f5;
        --text-secondary: #a1a1aa;

        --accent: #f59e0b;
        --accent-hover: #d97706;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--bg-main);
        color: var(--text-primary);
    }

    /* Sidebar */
    .app-sidebar {
        width: 250px;
        min-height: 100vh;
        background-color: var(--bg-sidebar);
        color: var(--text-primary);
        border-right: 1px solid var(--border-color);
    }

    .app-brand {
        font-size: 22px;
        font-weight: 700;
        padding: 24px;
        color: var(--text-primary);
    }

    /* Sidebar navigation */
    .sidebar-link {
        display: block;
        color: var(--text-secondary);
        text-decoration: none;
        padding: 12px 24px;
        transition: 0.2s;
    }

    .sidebar-link:hover,
    .sidebar-link.active {
        color: var(--accent);
        background-color: #242018;
    }

    /* Main content */
    .app-content {
        flex: 1;
        min-width: 0;
    }

    .app-header {
        background-color: var(--bg-sidebar);
        padding: 20px 30px;
        border-bottom: 1px solid var(--border-color);
    }

    .app-header h5 {
        color: var(--text-primary);
    }

    .app-main {
        padding: 30px;
    }

    /* Cards */
    .content-card {
        background-color: var(--bg-card);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    }

    .content-card:hover {
        border-color: #45454a;
    }

    /* Text */
    .text-muted {
        color: var(--text-secondary) !important;
    }

    /* Forms */
    .form-control,
    .form-select {
        background-color: #101012;
        color: var(--text-primary);
        border-color: var(--border-color);
    }

    .form-control::placeholder {
        color: #71717a;
    }

    .form-control:focus,
    .form-select:focus {
        background-color: #101012;
        color: var(--text-primary);
        border-color: var(--accent);
        box-shadow: 0 0 0 0.2rem rgba(245, 158, 11, 0.15);
    }

    .form-select option {
        background-color: var(--bg-card);
        color: var(--text-primary);
    }

    /* Tables */
    .table {
        color: var(--text-primary);
        border-color: var(--border-color);
    }

    .table thead th {
        background-color: #202024;
        color: var(--text-secondary);
        border-color: var(--border-color);
        font-size: 13px;
        font-weight: 500;
    }

    .table tbody td {
        background-color: var(--bg-card);
        color: var(--text-primary);
        border-color: var(--border-color);
    }

    .table-hover tbody tr:hover td {
        background-color: var(--bg-card-hover);
        color: var(--text-primary);
    }

    /* Buttons */
    .btn-primary {
        background-color: var(--accent);
        border-color: var(--accent);
        color: #18181b;
        font-weight: 600;
    }

    .btn-primary:hover {
        background-color: var(--accent-hover);
        border-color: var(--accent-hover);
        color: #18181b;
    }

    .btn-outline-secondary {
        color: var(--text-secondary);
        border-color: var(--border-color);
    }

    .btn-outline-secondary:hover {
        background-color: var(--bg-card-hover);
        color: var(--text-primary);
        border-color: #45454a;
    }

    /* Pagination */
    .pagination .page-link {
        background-color: var(--bg-card);
        color: var(--text-secondary);
        border-color: var(--border-color);
    }

    .pagination .page-item.active .page-link {
        background-color: var(--accent);
        color: #18181b;
        border-color: var(--accent);
    }

    .pagination .page-item.disabled .page-link {
        background-color: #101012;
        color: #52525b;
        border-color: var(--border-color);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .app-sidebar {
            width: 190px;
        }

        .app-main {
            padding: 16px;
        }
    }
    </style>

    @stack('styles')
</head>
<body>
    <div class="d-flex">
        <aside class="app-sidebar">
            <div class="app-brand">
                StockPilot
            </div>

            <nav>
                <a href="#" class="sidebar-link">
                    Dashboard
                </a>

                <a href="{{ route('inventory.index') }}"
                   class="sidebar-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                    Inventory
                </a>
            </nav>
        </aside>

        <div class="app-content">
            <header class="app-header">
                <h5 class="mb-0">@yield('page-title', 'Dashboard')</h5>
            </header>

            <main class="app-main">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>
</html>
