<?php
require_once __DIR__ . '/app/bootstrap.php';

$lockFile = __DIR__ . '/.installed';
if (is_file($lockFile) && env_value('APP_ENV', 'production') === 'production') {
    http_response_code(403);
    page_head('Installer Locked | FEDPOFFA Portal', 'Installer is locked.');
    echo '<main class="portal-shell min-h-screen px-4 py-8 dark:text-slate-100"><section class="mx-auto grid min-h-[80vh] max-w-3xl place-items-center"><div class="card w-full rounded-lg p-6 text-center sm:p-8"><div class="mx-auto grid h-14 w-14 place-items-center rounded-lg bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-200"><span data-icon="save"></span></div><h1 class="mt-5 text-3xl font-black text-slate-950 dark:text-white">Installer Locked</h1><p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-600 dark:text-slate-300">The database installer has already been completed.</p><div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row"><a class="btn-primary" href="' . e(url_for('/')) . '"><span data-icon="table"></span> Homepage</a></div></div></section></main>' . '<div id="toastStack" class="fixed right-3 top-20 z-[90] grid w-[calc(100vw-1.5rem)] max-w-sm gap-3 sm:right-5"></div></body></html>';
    exit;
}

if (!post_request()) {
    page_head('Install FEDPOFFA Portal', 'Install database schema.');
    echo '<main class="portal-shell min-h-screen px-4 py-8 dark:text-slate-100"><section class="mx-auto grid min-h-[80vh] max-w-4xl place-items-center"><div class="card w-full rounded-lg p-6 sm:p-8"><div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center"><div><div class="page-kicker"><span data-icon="save"></span> Setup</div><h1 class="mt-4 text-3xl font-black text-slate-950 dark:text-white sm:text-4xl">Install Database Schema</h1><p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">Confirm that .env contains valid MySQL credentials, then run the installer once. The setup will create the portal tables and seed the super admin account from your environment settings.</p></div><div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-bold text-green-800 dark:border-green-500/25 dark:bg-green-500/15 dark:text-green-200">Ready to install</div></div><form class="mt-7 flex flex-col gap-3 sm:flex-row" method="post">' . csrf_field() . '<button class="btn-primary" type="submit"><span data-icon="save"></span> Run Installer</button><a class="btn-secondary" href="' . e(url_for('/')) . '">Homepage</a></form></div></section></main><div id="toastStack" class="fixed right-3 top-20 z-[90] grid w-[calc(100vw-1.5rem)] max-w-sm gap-3 sm:right-5"></div></body></html>';
    exit;
}

verify_csrf();
$sql = file_get_contents(__DIR__ . '/database/schema.sql');
if ($sql === false) {
    render_error_page(
        500,
        'Installation Failed',
        'Unable to read the database schema file. Confirm that database/schema.sql exists and try again.',
        [
            ['label' => 'Try Again', 'href' => url_for('/install'), 'class' => 'btn-primary', 'icon' => 'save'],
            ['label' => 'Homepage', 'href' => url_for('/'), 'class' => 'btn-secondary', 'icon' => 'table'],
        ]
    );
}

try {
    db()->exec($sql);
    seed_super_admin();
    file_put_contents($lockFile, 'installed ' . date('c'));
    page_head('Installation Complete | FEDPOFFA Portal', 'Installation complete.');
    echo '<main class="portal-shell min-h-screen px-4 py-8 dark:text-slate-100"><section class="mx-auto grid min-h-[80vh] max-w-4xl place-items-center"><div class="card w-full rounded-lg p-6 text-center sm:p-8"><div class="mx-auto grid h-16 w-16 place-items-center rounded-lg bg-green-50 text-fedpoffa-blue dark:bg-green-500/15 dark:text-green-300"><span data-icon="save"></span></div><div class="page-kicker mt-6"><span data-icon="building"></span> Setup complete</div><h1 class="mt-4 text-3xl font-black text-slate-950 dark:text-white sm:text-4xl">Installation Complete</h1><p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">The database schema has been installed successfully. The super admin account has been seeded from your .env configuration.</p><div class="mx-auto mt-6 grid max-w-2xl gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-left text-sm font-bold text-green-800 dark:border-green-500/25 dark:bg-green-500/15 dark:text-green-200 sm:grid-cols-2"><span>Database tables created</span><span>Super admin seeded</span></div><div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row"><a class="btn-primary" href="' . e(url_for('/admin/login')) . '"><span data-icon="login"></span> Go to Admin Login</a><a class="btn-secondary" href="' . e(url_for('/')) . '">Go to Homepage</a></div></div></section></main><div id="toastStack" class="fixed right-3 top-20 z-[90] grid w-[calc(100vw-1.5rem)] max-w-sm gap-3 sm:right-5"></div></body></html>';
} catch (Throwable $e) {
    http_response_code(500);
    page_head('Installation Failed | FEDPOFFA Portal', 'Installation failed.');
    echo '<main class="portal-shell min-h-screen px-4 py-8 dark:text-slate-100"><section class="mx-auto grid min-h-[80vh] max-w-3xl place-items-center"><div class="card w-full rounded-lg p-6 sm:p-8"><div class="page-kicker"><span data-icon="x"></span> Setup error</div><h1 class="mt-4 text-3xl font-black text-slate-950 dark:text-white">Installation Failed</h1><p class="mt-3 text-sm leading-6 text-red-700 dark:text-red-200">' . e($e->getMessage()) . '</p><div class="mt-6 flex flex-col gap-3 sm:flex-row"><a class="btn-primary" href="' . e(url_for('/install')) . '"><span data-icon="save"></span> Try Again</a><a class="btn-secondary" href="' . e(url_for('/')) . '">Homepage</a></div></div></section></main><div id="toastStack" class="fixed right-3 top-20 z-[90] grid w-[calc(100vw-1.5rem)] max-w-sm gap-3 sm:right-5"></div></body></html>';
}
