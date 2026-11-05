<?php
require_once __DIR__ . '/../app/bootstrap.php';
$admin = require_admin();
$studentId = (int) field('id');
$student = db_one(
    'SELECT s.*, f.name faculty_name, d.name department_name, ss.name admission_session_name
     FROM students s
     JOIN faculties f ON f.id = s.faculty_id
     JOIN departments d ON d.id = s.department_id
     JOIN sessions ss ON ss.id = s.admission_session_id
     WHERE s.id = ?',
    [$studentId]
);

if (!$student) {
    flash_set('error', 'Student record not found.');
    redirect_to('/admin/students');
}

$faculties = db_all('SELECT * FROM faculties WHERE status = "active" ORDER BY name');
$departments = db_all('SELECT d.*, f.name faculty_name FROM departments d JOIN faculties f ON f.id = d.faculty_id ORDER BY f.name, d.name');
$sessions = sessions_all();

if (post_request()) {
    verify_csrf();
    try {
        $programme = strtoupper(field('programme_type'));
        $mode = strtoupper(field('study_mode'));
        $entryLevel = strtoupper(field('entry_level'));
        $fullName = normalize_full_name(field('full_name'));
        $nextOfKinFullName = normalize_full_name(field('next_of_kin_full_name'));
        $nextOfKinEmail = field('next_of_kin_email');
        $nextOfKinPhone = field('next_of_kin_phone');
        $nextOfKinRelationship = field('next_of_kin_relationship');
        if ($fullName === '' || full_name_has_number($fullName)) {
            throw new RuntimeException('Full name cannot contain numbers.');
        }
        if ($nextOfKinError = validate_next_of_kin_details($nextOfKinFullName, $nextOfKinEmail, $nextOfKinPhone, $nextOfKinRelationship, field('email'), field('phone'))) {
            throw new RuntimeException($nextOfKinError);
        }
        if (!validate_programme($programme, $mode)) {
            throw new RuntimeException('Invalid programme combination. HND PT does not exist.');
        }
        if (!validate_entry_level($programme, $entryLevel)) {
            throw new RuntimeException('Selected level must match the programme type.');
        }
        if (!department_supports_programme((int) field('department_id'), (int) field('faculty_id'), $programme)) {
            throw new RuntimeException('Selected department is not available for the chosen programme type.');
        }

        $matric = nullable_field('matric_number');
        $matricLocked = $matric ? (int) field('matric_locked', '1') : 0;

        db_exec(
            'UPDATE students
             SET full_name = ?, email = ?, phone = ?, next_of_kin_full_name = ?, next_of_kin_email = ?, next_of_kin_phone = ?, next_of_kin_relationship = ?, address = ?, gender = ?,
                 faculty_id = ?, department_id = ?, programme_type = ?, entry_level = ?, study_mode = ?,
                 application_number = ?, matric_number = ?, matric_locked = ?,
                 admission_session_id = ?, status = ?
             WHERE id = ?',
            [
                $fullName,
                field('email'),
                field('phone'),
                $nextOfKinFullName,
                $nextOfKinEmail,
                $nextOfKinPhone,
                $nextOfKinRelationship,
                field('address'),
                field('gender'),
                (int) field('faculty_id'),
                (int) field('department_id'),
                $programme,
                $entryLevel,
                $mode,
                field('application_number'),
                $matric,
                $matricLocked,
                (int) field('admission_session_id'),
                field('status'),
                $studentId,
            ]
        );

        if (field('password') !== '') {
            if (!password_is_strong(field('password'))) {
                throw new RuntimeException('Password must include upper/lowercase letters and a number.');
            }
            db_exec('UPDATE students SET password_hash = ? WHERE id = ?', [password_hash(field('password'), PASSWORD_BCRYPT), $studentId]);
        }

        ensure_student_wallet($studentId);
        log_activity((int) $admin['id'], $studentId, 'admin_update_student', [
            'application_number' => field('application_number'),
            'matric_number' => $matric,
        ]);
        flash_set('success', 'Student biodata updated.');
        redirect_to('/admin/student_edit?id=' . $studentId);
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        $student = array_merge($student, $_POST);
    }
}

page_head('Edit Student | FEDPOFFA Admin', 'Edit student biodata.', ['assets/js/admin.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php admin_header('students'); ?>
  <main class="mx-auto max-w-5xl px-4 pb-28 pt-6 sm:px-6 lg:px-8">
    <form class="view card rounded-lg p-5 sm:p-7" method="post">
      <?= csrf_field() ?>
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <div class="page-kicker"><span data-icon="edit"></span> Student biodata</div>
          <h1 class="mt-4 text-3xl font-black text-slate-950 dark:text-white">Edit Student Record</h1>
          <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Admin-only correction page for biodata, application number, matric number, programme details, and account status.</p>
        </div>
        <a class="btn-secondary" href="./students"><span data-icon="x"></span> Back</a>
      </div>

      <div class="form-grid mt-6">
        <label class="form-label">Full Name<input class="input uppercase" name="full_name" value="<?= e($student['full_name']) ?>" pattern="[^0-9]*" title="Full name cannot contain numbers" data-name-input required></label>
        <label class="form-label">Email<input class="input" name="email" type="email" value="<?= e($student['email']) ?>" required></label>
        <label class="form-label">Application Number<input class="input" name="application_number" value="<?= e($student['application_number']) ?>" required><span class="form-help">Editable by admin only.</span></label>
        <label class="form-label">Matric Number<input class="input" name="matric_number" value="<?= e($student['matric_number']) ?>" placeholder="Enter or correct matric number"></label>
        <label class="form-label">Matric Lock<select class="input" name="matric_locked"><option value="1" <?= (int) $student['matric_locked'] === 1 ? 'selected' : '' ?>>Locked</option><option value="0" <?= (int) $student['matric_locked'] === 0 ? 'selected' : '' ?>>Unlocked</option></select><span class="form-help">Students cannot edit matric number when locked. Admins can still correct it here.</span></label>
        <label class="form-label">Phone<input class="input" name="phone" value="<?= e($student['phone']) ?>" required></label>
        <label class="form-label">Next of Kin / Guardian Full Name<input class="input uppercase" name="next_of_kin_full_name" value="<?= e($student['next_of_kin_full_name'] ?? '') ?>" pattern="[^0-9]*" title="Full name cannot contain numbers" data-name-input required></label>
        <label class="form-label">Next of Kin Relationship<input class="input" name="next_of_kin_relationship" value="<?= e($student['next_of_kin_relationship'] ?? '') ?>" placeholder="Parent, guardian, sibling" required></label>
        <label class="form-label">Next of Kin Email<input class="input" name="next_of_kin_email" type="email" value="<?= e($student['next_of_kin_email'] ?? '') ?>" required></label>
        <label class="form-label">Next of Kin Phone<input class="input" name="next_of_kin_phone" value="<?= e($student['next_of_kin_phone'] ?? '') ?>" required><span class="form-help">Must differ from the student's email address and phone number.</span></label>
        <label class="form-label">Gender<select class="input" name="gender"><?php foreach (['Female','Male','Other'] as $gender): ?><option <?= $student['gender'] === $gender ? 'selected' : '' ?>><?= e($gender) ?></option><?php endforeach; ?></select></label>
        <label class="form-label">Status<select class="input" name="status"><?php foreach (['active','suspended'] as $status): ?><option value="<?= e($status) ?>" <?= $student['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option><?php endforeach; ?></select></label>
        <label class="form-label">Faculty<select class="input" name="faculty_id" data-faculty-select required><?php foreach ($faculties as $faculty): ?><option value="<?= (int) $faculty['id'] ?>" <?= (int) $student['faculty_id'] === (int) $faculty['id'] ? 'selected' : '' ?>><?= e($faculty['name']) ?></option><?php endforeach; ?></select></label>
        <label class="form-label">Department<select class="input" name="department_id" data-department-select required><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (int) $student['department_id'] === (int) $department['id'] ? 'selected' : '' ?>><?= e($department['faculty_name'] . ' - ' . $department['name']) ?></option><?php endforeach; ?></select></label>
        <label class="form-label">Programme Type<select class="input" name="programme_type" data-programme-select><?php foreach (['ND','HND'] as $programme): ?><option <?= $student['programme_type'] === $programme ? 'selected' : '' ?>><?= e($programme) ?></option><?php endforeach; ?></select></label>
        <label class="form-label">Level<select class="input" name="entry_level" data-level-select><?php foreach (['ND1','ND2','HND1','HND2'] as $level): ?><option <?= ($student['entry_level'] ?? ($student['programme_type'] . '1')) === $level ? 'selected' : '' ?>><?= e($level) ?></option><?php endforeach; ?></select><span class="form-help">ND1/HND1 are freshers. ND2/HND2 are staylites.</span></label>
        <label class="form-label">Study Mode<select class="input" name="study_mode"><?php foreach (['FT','PT'] as $mode): ?><option <?= $student['study_mode'] === $mode ? 'selected' : '' ?>><?= e($mode) ?></option><?php endforeach; ?></select></label>
        <label class="form-label">Admission Session<select class="input" name="admission_session_id"><?php foreach ($sessions as $session): ?><option value="<?= (int) $session['id'] ?>" <?= (int) $student['admission_session_id'] === (int) $session['id'] ? 'selected' : '' ?>><?= e($session['name']) ?></option><?php endforeach; ?></select></label>
        <label class="form-label">Reset Password<input class="input" name="password" type="password" placeholder="Leave blank to keep current password"><span class="form-help">Minimum 8 characters with uppercase, lowercase, and number.</span></label>
        <label class="form-label">Address<input class="input" name="address" value="<?= e($student['address']) ?>"></label>
      </div>

      <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <button class="btn-primary w-full sm:w-auto" type="submit"><span data-icon="save"></span> Save Student Biodata</button>
        <a class="btn-secondary w-full sm:w-auto" href="./students"><span data-icon="users"></span> Student List</a>
      </div>
    </form>
  </main>
  <?php admin_mobile_nav('students'); ?>
</div><?php modal_and_toasts(); ?></body></html>
