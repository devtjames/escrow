<?php
declare(strict_types=1);

function asset_path(string $path): string
{
    $cleanPath = ltrim($path, '/');
    $file = BASE_PATH . '/' . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
    $version = is_file($file) ? '?v=' . filemtime($file) : '';
    return url_for('/' . $cleanPath) . $version;
}

function page_head(string $title, string $description, array $scripts = []): void
{
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="description" content="' . e($description) . '"><meta name="theme-color" content="#0B5D3B">';
    echo '<title>' . e($title) . '</title>';
    echo '<link rel="icon" type="image/png" sizes="32x32" href="' . e(asset_path('assets/img/favicon-32.png')) . '">';
    echo '<link rel="apple-touch-icon" href="' . e(asset_path('assets/img/apple-touch-icon.png')) . '">';
    echo '<link rel="stylesheet" href="' . e(asset_path('assets/css/styles.css')) . '">';
    echo '<script>window.AppConfig={csrfToken:"' . e(csrf_token()) . '",paystackPublicKey:"' . e(paystack_public_key()) . '",minimumDeposit:' . minimum_deposit_amount() . '};</script>';
    echo '<script src="' . e(asset_path('assets/js/ui.js')) . '" defer></script>';
    foreach ($scripts as $script) {
        echo '<script src="' . e(asset_path($script)) . '" defer></script>';
    }
    echo '</head><body class="font-sans antialiased">';
}

function brand_logo(string $context = 'student'): string
{
    $label = $context === 'admin' ? 'FEDPOFFA Admin' : 'FEDPOFFA Student Portal';
    return '<img src="' . e(asset_path('assets/img/logo2.png')) . '" alt="' . e($label) . '" class="h-11 w-auto max-w-[11rem] object-contain">';
}

function admin_role_label(array $admin): string
{
    return ucwords(str_replace('_', ' ', (string) ($admin['role'] ?? 'admin')));
}

function admin_page_title(string $active): string
{
    $labels = [
        'dashboard' => 'Dashboard',
        'students' => 'Students',
        'payments' => 'Payments',
        'fees' => 'Fees',
        'sessions' => 'Sessions',
        'faculties' => 'Faculties',
        'departments' => 'Departments',
        'admins' => 'Admin Accounts',
        'emails' => 'Email Center',
        'settings' => 'Settings',
    ];
    return $labels[$active] ?? 'Admin Panel';
}

function admin_breadcrumbs(string $active): void
{
    $group = '';
    if (in_array($active, ['sessions', 'faculties', 'departments'], true)) {
        $group = 'Academic Setup';
    } elseif ($active === 'admins') {
        $group = 'Administration';
    } elseif (in_array($active, ['emails', 'settings'], true)) {
        $group = 'Communication';
    }
    echo '<nav class="admin-breadcrumbs"><a href="' . e(url_for('/admin/dashboard')) . '">Dashboard</a><span>/</span>';
    if ($group !== '') {
        echo '<span>' . e($group) . '</span><span>/</span>';
    }
    echo '<b>' . e(admin_page_title($active)) . '</b></nav>';
}

function admin_status_badge(string $label, string $tone = 'gold'): string
{
    return '<span class="admin-badge ' . e($tone) . '">' . e($label) . '</span>';
}

function pagination_state(int $totalRows, int $perPage = 20, string $pageKey = 'page'): array
{
    $totalPages = max(1, (int) ceil($totalRows / max(1, $perPage)));
    $page = max(1, min($totalPages, (int) ($_GET[$pageKey] ?? 1)));
    return [
        'page' => $page,
        'per_page' => $perPage,
        'offset' => ($page - 1) * $perPage,
        'total_rows' => $totalRows,
        'total_pages' => $totalPages,
        'page_key' => $pageKey,
    ];
}

function render_pagination(array $pagination): void
{
    if (($pagination['total_pages'] ?? 1) <= 1) {
        return;
    }
    $page = (int) $pagination['page'];
    $totalPages = (int) $pagination['total_pages'];
    $pageKey = (string) ($pagination['page_key'] ?? 'page');
    $query = $_GET;
    $makeUrl = static function (int $target) use ($query, $pageKey): string {
        $next = $query;
        $next[$pageKey] = $target;
        return '?' . http_build_query($next);
    };
    echo '<nav class="admin-pagination" aria-label="Pagination">';
    echo '<a class="btn-secondary ' . ($page <= 1 ? 'is-disabled' : '') . '" href="' . e($makeUrl(max(1, $page - 1))) . '">Previous</a>';
    echo '<div class="admin-page-numbers">';
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    if ($start > 1) {
        echo '<a href="' . e($makeUrl(1)) . '">1</a>';
        if ($start > 2) {
            echo '<span>...</span>';
        }
    }
    for ($i = $start; $i <= $end; $i++) {
        echo '<a class="' . ($i === $page ? 'is-active' : '') . '" href="' . e($makeUrl($i)) . '">' . e((string) $i) . '</a>';
    }
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            echo '<span>...</span>';
        }
        echo '<a href="' . e($makeUrl($totalPages)) . '">' . e((string) $totalPages) . '</a>';
    }
    echo '</div>';
    echo '<a class="btn-secondary ' . ($page >= $totalPages ? 'is-disabled' : '') . '" href="' . e($makeUrl(min($totalPages, $page + 1))) . '">Next</a>';
    echo '</nav>';
}

function render_error_page(int $statusCode, string $title, string $message, array $actions = []): never
{
    http_response_code($statusCode);
    $actions = $actions ?: [
        ['label' => 'Homepage', 'href' => url_for('/'), 'class' => 'btn-primary', 'icon' => 'table'],
    ];
    page_head($title . ' | FEDPOFFA Portal', $message);
    echo '<main class="portal-shell min-h-screen px-4 py-8 dark:text-slate-100">';
    echo '<section class="mx-auto grid min-h-[80vh] max-w-4xl place-items-center">';
    echo '<div class="card w-full overflow-hidden rounded-lg p-6 sm:p-8">';
    echo '<div class="grid gap-7 lg:grid-cols-[1fr_13rem] lg:items-center">';
    echo '<div><div class="page-kicker"><span data-icon="x"></span> Error ' . e((string) $statusCode) . '</div>';
    echo '<h1 class="mt-4 text-3xl font-black text-slate-950 dark:text-white sm:text-4xl">' . e($title) . '</h1>';
    echo '<p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">' . e($message) . '</p>';
    echo '<div class="mt-7 flex flex-col gap-3 sm:flex-row">';
    foreach ($actions as $action) {
        $class = $action['class'] ?? 'btn-secondary';
        $icon = $action['icon'] ?? '';
        echo '<a class="' . e($class) . '" href="' . e($action['href'] ?? url_for('/')) . '">';
        if ($icon !== '') {
            echo '<span data-icon="' . e($icon) . '"></span>';
        }
        echo e($action['label'] ?? 'Continue') . '</a>';
    }
    echo '</div></div>';
    echo '<div class="rounded-lg border border-green-200 bg-green-50 p-5 text-center dark:border-green-500/25 dark:bg-green-500/15">';
    echo '<div class="text-5xl font-black text-fedpoffa-blue dark:text-green-300">' . e((string) $statusCode) . '</div>';
    echo '<p class="mt-2 text-xs font-black uppercase text-slate-500 dark:text-slate-400">FEDPOFFA Portal</p>';
    echo '</div></div></div></section></main>';
    echo '<div id="toastStack" class="fixed right-3 top-20 z-[90] grid w-[calc(100vw-1.5rem)] max-w-sm gap-3 sm:right-5"></div></body></html>';
    exit;
}

function render_flash_toasts(): void
{
    $messages = flash_all();
    if (!$messages) {
        return;
    }
    echo '<script>window.addEventListener("DOMContentLoaded",()=>{';
    foreach ($messages as $message) {
        echo 'window.FedpoffaUI?.toast(' . json_encode($message['type']) . ',' . json_encode($message['message']) . ');';
    }
    echo '});</script>';
}

function student_header(string $active = ''): void
{
    echo '<header class="glass-nav sticky top-0 z-50"><nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">';
    echo '<a href="' . e(url_for('/student/dashboard')) . '" class="flex min-w-0 items-center gap-3">' . brand_logo('student') . '</a>';
    echo '<div class="hidden items-center gap-2 md:flex">';
    $links = ['dashboard' => 'Dashboard', 'payment' => 'Payment', 'transactions' => 'History', 'biodata' => 'Biodata'];
    foreach ($links as $key => $label) {
        echo '<a class="nav-link' . ($active === $key ? ' is-active' : '') . '" href="./' . $key . '">' . $label . '</a>';
    }
    echo '<a class="nav-link" href="./logout">Logout</a></div><button data-theme-toggle class="icon-btn" type="button" aria-label="Toggle dark mode"></button></nav></header>';
}

function student_public_header(string $active = ''): void
{
    echo '<header class="glass-nav sticky top-0 z-50"><nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">';
    echo '<a href="' . e(url_for('/')) . '" class="flex min-w-0 items-center gap-3">' . brand_logo('student') . '</a>';
    echo '<div class="hidden items-center gap-2 md:flex"><a class="nav-link" href="' . e(url_for('/')) . '">Home</a><a class="nav-link' . ($active === 'login' ? ' is-active' : '') . '" href="' . e(url_for('/student/login')) . '">Login</a><a class="nav-link' . ($active === 'register' ? ' is-active' : '') . '" href="' . e(url_for('/student/register')) . '">Register</a></div>';
    echo '<button data-theme-toggle class="icon-btn" type="button" aria-label="Toggle dark mode"></button></nav></header>';
}

function student_public_mobile_nav(string $active = ''): void
{
    echo '<nav class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white/95 px-2 py-2 shadow-[0_-18px_40px_rgba(15,23,42,0.12)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/95 md:hidden"><div class="mx-auto grid max-w-md grid-cols-3 gap-1">';
    $links = ['home' => ['table', 'Home', url_for('/')], 'login' => ['login', 'Login', url_for('/student/login')], 'register' => ['userPlus', 'Register', url_for('/student/register')]];
    foreach ($links as $key => $link) {
        echo '<a class="mobile-nav-link' . ($active === $key ? ' is-active' : '') . '" href="' . $link[2] . '"><span data-icon="' . $link[0] . '"></span><span>' . $link[1] . '</span></a>';
    }
    echo '</div></nav>';
}

function student_mobile_nav(string $active = ''): void
{
    echo '<nav class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white/95 px-2 py-2 shadow-[0_-18px_40px_rgba(15,23,42,0.12)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/95 md:hidden"><div class="mx-auto grid max-w-md grid-cols-5 gap-1">';
    $links = ['dashboard' => ['table', 'Dash'], 'payment' => ['wallet', 'Pay'], 'transactions' => ['receipt', 'History'], 'biodata' => ['userPlus', 'Biodata']];
    foreach ($links as $key => $link) {
        echo '<a class="mobile-nav-link' . ($active === $key ? ' is-active' : '') . '" href="./' . $key . '"><span data-icon="' . $link[0] . '"></span><span>' . $link[1] . '</span></a>';
    }
    echo '<a class="mobile-nav-link" href="./logout"><span data-icon="login"></span><span>Logout</span></a></div></nav>';
}

function admin_header(string $active = ''): void
{
    $admin = current_admin() ?? [];
    $academicOpen = in_array($active, ['sessions', 'faculties', 'departments'], true);
    $communicationOpen = in_array($active, ['emails', 'settings'], true);
    $isSuperAdmin = ($admin['role'] ?? '') === 'super_admin';
    echo '<div class="admin-progress" aria-hidden="true"></div>';
    echo '<aside class="admin-sidebar" data-admin-sidebar>';
    echo '<div class="admin-brand"><a href="' . e(url_for('/admin/dashboard')) . '" title="Dashboard">' . brand_logo('admin') . '</a><button class="admin-collapse-btn" data-admin-collapse type="button" title="Collapse sidebar"><span data-icon="panelLeft"></span></button></div>';
    echo '<nav class="admin-side-nav" aria-label="Admin navigation">';
    $mainLinks = [
        'dashboard' => ['table', 'Dashboard'],
        'students' => ['users', 'Students'],
        'payments' => ['receipt', 'Payments'],
        'fees' => ['wallet', 'Fees'],
    ];
    foreach ($mainLinks as $key => $link) {
        echo '<a class="' . ($active === $key ? 'is-active' : '') . '" href="' . e(url_for('/admin/' . $key)) . '" title="' . e($link[1]) . '"><span class="admin-nav-icon" data-icon="' . e($link[0]) . '"></span><span class="admin-nav-text">' . e($link[1]) . '</span></a>';
    }
    if ($isSuperAdmin) {
        echo '<a class="' . ($active === 'admins' ? 'is-active' : '') . '" href="' . e(url_for('/admin/admins')) . '" title="Admin Accounts"><span class="admin-nav-icon" data-icon="users"></span><span class="admin-nav-text">Admin Accounts</span></a>';
    }
    echo '<div class="admin-nav-group ' . ($academicOpen ? 'is-open' : '') . '"><button class="admin-nav-toggle" data-admin-nav-toggle type="button" title="Academic Setup"><span class="admin-nav-icon" data-icon="building"></span><span class="admin-nav-text">Academic Setup</span><span class="admin-nav-chevron" data-icon="chevronDown"></span></button><div class="admin-nav-children">';
    foreach (['sessions' => ['calendar', 'Sessions'], 'faculties' => ['building', 'Faculties'], 'departments' => ['book', 'Departments']] as $key => $link) {
        echo '<a class="' . ($active === $key ? 'is-active' : '') . '" href="' . e(url_for('/admin/' . $key)) . '" title="' . e($link[1]) . '"><span class="admin-nav-icon" data-icon="' . e($link[0]) . '"></span><span class="admin-nav-text">' . e($link[1]) . '</span></a>';
    }
    echo '</div></div>';
    echo '<div class="admin-nav-group ' . ($communicationOpen ? 'is-open' : '') . '"><button class="admin-nav-toggle" data-admin-nav-toggle type="button" title="Communication"><span class="admin-nav-icon" data-icon="mail"></span><span class="admin-nav-text">Communication</span><span class="admin-nav-chevron" data-icon="chevronDown"></span></button><div class="admin-nav-children">';
    foreach (['emails' => ['mail', 'Email Center'], 'settings' => ['settings', 'Settings']] as $key => $link) {
        echo '<a class="' . ($active === $key ? 'is-active' : '') . '" href="' . e(url_for('/admin/' . $key)) . '" title="' . e($link[1]) . '"><span class="admin-nav-icon" data-icon="' . e($link[0]) . '"></span><span class="admin-nav-text">' . e($link[1]) . '</span></a>';
    }
    echo '</div></div>';
    echo '</nav><div class="admin-sidebar-footer"><b>' . e($admin['full_name'] ?? 'Administrator') . '</b>' . admin_status_badge(admin_role_label($admin)) . '<a href="' . e(url_for('/admin/logout')) . '" title="Logout"><span class="admin-nav-icon" data-icon="login"></span><span class="admin-nav-text">Logout</span></a></div></aside>';
    echo '<button class="admin-sidebar-backdrop" data-admin-drawer-close type="button" aria-label="Close admin menu"></button>';
    echo '<header class="admin-topbar"><div class="admin-top-title"><button class="admin-menu-btn" data-admin-drawer-open type="button" aria-label="Open admin menu"><span data-icon="menu"></span></button><div><h1>' . e(admin_page_title($active)) . '</h1><p>FEDPOFFA school fees and institutional payments management.</p></div></div><div class="admin-top-actions"><form class="admin-search" method="get" action="' . e(url_for('/admin/students')) . '"><input name="q" placeholder="Search students..."></form><button data-theme-toggle class="icon-btn" type="button" aria-label="Toggle dark mode"></button>' . admin_status_badge(admin_role_label($admin)) . '</div></header>';
}

function admin_public_header(string $active = ''): void
{
    echo '<header class="glass-nav sticky top-0 z-50"><nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">';
    echo '<a href="' . e(url_for('/admin/login')) . '" class="flex min-w-0 items-center gap-3">' . brand_logo('admin') . '</a>';
    echo '<div class="hidden items-center gap-2 xl:flex"><a class="nav-link' . ($active === 'login' ? ' is-active' : '') . '" href="' . e(url_for('/admin/login')) . '">Login</a><a class="nav-link' . ($active === 'register' ? ' is-active' : '') . '" href="' . e(url_for('/admin/register')) . '">Register</a></div><button data-theme-toggle class="icon-btn" type="button" aria-label="Toggle dark mode"></button></nav></header>';
}

function admin_mobile_nav(string $active = ''): void
{
    echo '';
}

function modal_and_toasts(): void
{
    echo '<div id="modalRoot" class="fixed inset-0 z-[70] hidden items-end justify-center bg-slate-950/60 p-3 backdrop-blur-sm sm:items-center" role="dialog" aria-modal="true"></div>';
    echo '<div id="toastStack" class="fixed right-3 top-20 z-[90] grid w-[calc(100vw-1.5rem)] max-w-sm gap-3 sm:right-5"></div>';
    render_flash_toasts();
}
