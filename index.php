<?php require_once __DIR__ . '/app/bootstrap.php'; ?>
<?php page_head('FEDPOFFA Student Fee Portal', 'FEDPOFFA student school fee payment portal.'); ?>
<div id="pageLoader" class="fixed inset-0 z-[80] grid place-items-center bg-white transition-opacity duration-300 dark:bg-slate-950">
  <div class="grid place-items-center gap-4">
    <div class="relative h-16 w-16"><span class="absolute inset-0 rounded-full border-4 border-slate-200 dark:border-slate-800"></span><span class="absolute inset-0 rounded-full border-4 border-fedpoffa-blue border-t-transparent motion-safe:animate-spin"></span></div>
    <img class="h-12 w-auto object-contain" src="<?= e(asset_path('assets/img/logo2.png')) ?>" alt="FEDPOFFA Student Portal">
  </div>
</div>
<div class="portal-shell dark:text-slate-100">
  <header class="glass-nav sticky top-0 z-50">
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
      <a href="./index" class="flex min-w-0 items-center gap-3"><?= brand_logo('student') ?></a>
      <div class="hidden items-center gap-2 md:flex"><a class="nav-link is-active" href="./index">Home</a><a class="nav-link" href="./student/login">Login</a><a class="nav-link" href="./student/register">Register</a></div>
      <button data-theme-toggle class="icon-btn" type="button" aria-label="Toggle dark mode"></button>
    </nav>
  </header>
  <main class="mx-auto max-w-7xl px-4 pb-24 pt-6 sm:px-6 lg:px-8">
    <section class="view grid gap-8 lg:grid-cols-[1.02fr_0.98fr] lg:items-center">
      <div class="py-4 sm:py-8 lg:py-14">
        <div class="page-kicker"><span data-icon="wallet"></span> Student payments only</div>
        <h1 class="mt-4 max-w-3xl text-4xl font-black leading-tight text-slate-950 dark:text-white sm:text-5xl lg:text-6xl">FEDPOFFA School Fee Payment System</h1>
        <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300 sm:text-lg">A focused student portal for fee overview, deposits, transaction history, checkout summaries, and printable receipt previews.</p>
        <div class="mt-7 flex flex-col gap-3 sm:flex-row"><a class="btn-primary w-full sm:w-auto" href="./student/login"><span data-icon="login"></span> Student Login</a><a class="btn-secondary w-full sm:w-auto" href="./student/register"><span data-icon="userPlus"></span> Student Registration</a></div>
      </div>
      <div class="overflow-hidden rounded-lg border border-slate-200 bg-slate-950 shadow-2xl shadow-green-950/20 dark:border-slate-700">
        <div class="relative min-h-[21rem] sm:min-h-[28rem]">
          <img class="absolute inset-0 h-full w-full object-cover opacity-75" src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/ca/Federal_polytechnic_Offa_entrance.jpg/1280px-Federal_polytechnic_Offa_entrance.jpg" alt="Entrance sign of The Federal Polytechnic Offa" loading="eager" referrerpolicy="no-referrer">
          <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/45 to-transparent"></div>
          <div class="absolute inset-x-0 bottom-0 p-5 text-white sm:p-7">
            <div class="grid grid-cols-3 gap-2 rounded-lg border border-white/15 bg-white/10 p-3 backdrop-blur-md">
              <div class="rounded-md bg-white/10 p-3 text-center"><div class="text-lg font-black sm:text-2xl">15k+</div><div class="text-[0.7rem] font-bold uppercase tracking-wide text-white/75">Students</div></div>
              <div class="rounded-md bg-white/10 p-3 text-center"><div class="text-lg font-black sm:text-2xl">43</div><div class="text-[0.7rem] font-bold uppercase tracking-wide text-white/75">Programs</div></div>
              <div class="rounded-md bg-white/10 p-3 text-center"><div class="text-lg font-black sm:text-2xl">98%</div><div class="text-[0.7rem] font-bold uppercase tracking-wide text-white/75">Verified</div></div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section class="mt-8 grid gap-4 sm:grid-cols-3">
      <article class="card rounded-lg p-5 transition hover:-translate-y-1 hover:shadow-2xl"><h2 class="flex items-center gap-2 text-lg font-black text-slate-950 dark:text-white"><span data-icon="wallet"></span> Fee Wallet</h2><p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Track total fee, paid amount, outstanding balance, and payment progress.</p></article>
      <article class="card rounded-lg p-5 transition hover:-translate-y-1 hover:shadow-2xl"><h2 class="flex items-center gap-2 text-lg font-black text-slate-950 dark:text-white"><span data-icon="receipt"></span> Receipts</h2><p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Preview and print clean A4-ready payment receipts.</p></article>
      <article class="card rounded-lg p-5 transition hover:-translate-y-1 hover:shadow-2xl"><h2 class="flex items-center gap-2 text-lg font-black text-slate-950 dark:text-white"><span data-icon="table"></span> History</h2><p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Search and sort deposits with reference numbers and payment methods.</p></article>
    </section>
    <footer class="mt-10 pb-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400">Campus image: Ajia4ty, Wikimedia Commons, CC BY-SA 4.0.</footer>
  </main>
  <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white/95 px-2 py-2 shadow-[0_-18px_40px_rgba(15,23,42,0.12)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/95 md:hidden"><div class="mx-auto grid max-w-md grid-cols-3 gap-1"><a class="mobile-nav-link is-active" href="./index"><span data-icon="table"></span><span>Home</span></a><a class="mobile-nav-link" href="./student/login"><span data-icon="login"></span><span>Login</span></a><a class="mobile-nav-link" href="./student/register"><span data-icon="userPlus"></span><span>Register</span></a></div></nav>
</div>
<div id="toastStack" class="fixed right-3 top-20 z-[90] grid w-[calc(100vw-1.5rem)] max-w-sm gap-3 sm:right-5"></div>
<?php render_flash_toasts(); ?>
</body></html>
