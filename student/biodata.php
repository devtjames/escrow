<?php
require_once __DIR__ . '/../app/bootstrap.php';
$student = require_student();

if (post_request()) {
    verify_csrf();
    try {
        $matric = field('matric_number');
        if ($matric !== '') {
            if ((int) $student['matric_locked'] === 1) {
                throw new RuntimeException('Matric number is locked and cannot be edited.');
            }
            db_exec(
                'UPDATE students SET matric_number = ?, matric_locked = 1 WHERE id = ?',
                [$matric, (int) $student['id']]
            );
            flash_set('success', 'Matric number saved and locked permanently.');
        }

        db_exec(
            'UPDATE students SET phone = ?, address = ? WHERE id = ?',
            [field('phone'), field('address'), (int) $student['id']]
        );
        redirect_to('/student/biodata');
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
}

page_head('Student Biodata | FEDPOFFA', 'Student biodata settings.', ['assets/js/student.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php student_header('biodata'); ?>
  <main class="mx-auto max-w-5xl px-4 pb-28 pt-6 sm:px-6 lg:px-8">
    <form class="view card rounded-lg p-5 sm:p-7" method="post">
      <?= csrf_field() ?>
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <div class="page-kicker"><span data-icon="userPlus"></span> Student biodata</div>
          <h1 class="mt-4 text-3xl font-black text-slate-950 dark:text-white">Biodata Details</h1>
          <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Update your contact details and add your matric number once it has been issued.</p>
        </div>
        <span class="status-pill <?= (int) $student['matric_locked'] === 1 ? 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300' : 'bg-amber-50 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200' ?>"><?= (int) $student['matric_locked'] === 1 ? 'MATRIC LOCKED' : 'MATRIC PENDING' ?></span>
      </div>

      <div class="form-grid mt-6">
        <label class="form-label">Full Name<input class="input" value="<?= e($student['full_name']) ?>" disabled></label>
        <label class="form-label">Email<input class="input" value="<?= e($student['email']) ?>" disabled></label>
        <label class="form-label">Application Number<input class="input" value="<?= e($student['application_number']) ?>" disabled></label>
        <label class="form-label">Matric Number<input class="input" name="matric_number" value="<?= e($student['matric_number']) ?>" placeholder="Enter matric number when issued" <?= (int) $student['matric_locked'] === 1 ? 'disabled' : '' ?>><span class="form-help">Once saved, this field locks permanently and can only be changed by an administrator.</span></label>
        <label class="form-label">Phone<input class="input" name="phone" value="<?= e($student['phone']) ?>"></label>
        <label class="form-label">Address<input class="input" name="address" value="<?= e($student['address']) ?>"></label>
        <label class="form-label">Gender<input class="input" value="<?= e($student['gender']) ?>" disabled></label>
        <label class="form-label">Programme<input class="input" value="<?= e($student['programme_type'] . ' ' . $student['study_mode'] . ' - ' . student_level($student)) ?>" disabled></label>
        <label class="form-label">Faculty<input class="input" value="<?= e($student['faculty_name']) ?>" disabled></label>
        <label class="form-label">Department<input class="input" value="<?= e($student['department_name']) ?>" disabled></label>
      </div>

      <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <button class="btn-primary w-full sm:w-auto" type="submit"><span data-icon="save"></span> Save Biodata</button>
        <a class="btn-secondary w-full sm:w-auto" href="./dashboard"><span data-icon="table"></span> Back to Dashboard</a>
      </div>
    </form>
  </main>
  <?php student_mobile_nav('biodata'); ?>
</div><?php modal_and_toasts(); ?></body></html>
