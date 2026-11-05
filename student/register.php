<?php
require_once __DIR__ . '/../app/bootstrap.php';
$faculties = db_all('SELECT * FROM faculties WHERE status = "active" ORDER BY name');
$sessions = sessions_all();
$active = active_session();

if (post_request()) {
    verify_csrf();
    $programme = strtoupper(field('programme_type'));
    $mode = strtoupper(field('study_mode'));
    $entryLevel = strtoupper(field('entry_level'));
    $password = field('password');
    $confirm = field('password_confirm');
    $matric = nullable_field('matric_number');
    $admissionSession = (int) field('admission_session_id', $active['id'] ?? 0);
    $fullName = normalize_full_name(field('full_name'));
    $nextOfKinFullName = normalize_full_name(field('next_of_kin_full_name'));
    $nextOfKinEmail = field('next_of_kin_email');
    $nextOfKinPhone = field('next_of_kin_phone');
    $nextOfKinRelationship = field('next_of_kin_relationship');

    if ($fullName === '' || full_name_has_number($fullName)) {
        flash_set('error', 'Full name cannot contain numbers.');
    } elseif ($nextOfKinError = validate_next_of_kin_details($nextOfKinFullName, $nextOfKinEmail, $nextOfKinPhone, $nextOfKinRelationship, field('email'), field('phone'))) {
        flash_set('error', $nextOfKinError);
    } elseif (!validate_programme($programme, $mode)) {
        flash_set('error', 'Invalid programme combination. HND PT does not exist.');
    } elseif (!validate_entry_level($programme, $entryLevel)) {
        flash_set('error', 'Selected level must match the programme type.');
    } elseif (!department_supports_programme((int) field('department_id'), (int) field('faculty_id'), $programme)) {
        flash_set('error', 'Selected department is not available for the chosen programme type.');
    } elseif ($password !== $confirm || !password_is_strong($password)) {
        flash_set('error', 'Passwords must match and include upper/lowercase letters and a number.');
    } elseif (db_one('SELECT id FROM students WHERE email = ? LIMIT 1', [field('email')])) {
        flash_set('error', 'A student account already exists with this email address. Please login instead.');
    } elseif (db_one('SELECT id FROM students WHERE application_number = ? LIMIT 1', [field('application_number')])) {
        flash_set('error', 'A student account already exists with this application number. Please login instead.');
    } elseif ($matric && db_one('SELECT id FROM students WHERE matric_number = ? LIMIT 1', [$matric])) {
        flash_set('error', 'A student account already exists with this matric number. Please login or contact admin.');
    } else {
        try {
            db_exec(
                'INSERT INTO students (full_name, email, password_hash, phone, next_of_kin_full_name, next_of_kin_email, next_of_kin_phone, next_of_kin_relationship, address, gender, faculty_id, department_id, programme_type, entry_level, study_mode, application_number, matric_number, matric_locked, admission_session_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $fullName,
                    field('email'),
                    password_hash($password, PASSWORD_BCRYPT),
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
                    $matric ? 1 : 0,
                    $admissionSession,
                ]
            );
            $studentId = (int) db()->lastInsertId();
            ensure_student_wallet($studentId);
            sendMail(field('email'), 'Registration Successful', '<p>Your FEDPOFFA fee portal registration was successful.</p>');
            student_login(field('email'), $password);
            redirect_to('/student/dashboard');
        } catch (Throwable $e) {
            flash_set('error', 'Registration failed: ' . $e->getMessage());
        }
    }
}

page_head('Student Registration | FEDPOFFA', 'FEDPOFFA student registration.', ['assets/js/student.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php student_public_header('register'); ?>
  <main class="mx-auto max-w-5xl px-4 pb-24 pt-8 sm:px-6 lg:px-8">
    <form class="view card rounded-lg p-5 sm:p-7" method="post" data-student-register>
      <?= csrf_field() ?>
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><div class="page-kicker"><span data-icon="userPlus"></span> Student registration</div><h1 class="mt-4 text-3xl font-black text-slate-950 dark:text-white">Create Student Account</h1><p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Register with your application details. Matric number is optional until assigned.</p></div><a class="btn-secondary" href="<?= e(url_for('/student/login')) ?>">Login</a></div>
      <div class="form-grid mt-6">
        <label class="form-label">Full Name<input class="input uppercase" name="full_name" placeholder="ADEBAYO MARIAM" pattern="[^0-9]*" title="Full name cannot contain numbers" data-name-input required></label>
        <label class="form-label">Application Number<input class="input" name="application_number" placeholder="APP/2025/0001" required></label>
        <label class="form-label">Matric Number<input class="input" name="matric_number" placeholder="FPO/HND/24/0412"><span class="form-help">Once saved, the matric number is locked permanently.</span></label>
        <label class="form-label">Email<input class="input" name="email" type="email" placeholder="student@fedpoffa.edu.ng" required></label>
        <label class="form-label">Phone<input class="input" name="phone" type="tel" placeholder="08000000000" required></label>
        <div class="form-wide rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900"><h2 class="text-base font-black text-slate-950 dark:text-white">Next of Kin / Guardian</h2><p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">They will receive payment receipt notifications. These details can only be changed by an administrator after registration.</p><div class="mt-4 form-grid"><label class="form-label">Full Name<input class="input uppercase" name="next_of_kin_full_name" pattern="[^0-9]*" title="Full name cannot contain numbers" data-name-input required></label><label class="form-label">Relationship<input class="input" name="next_of_kin_relationship" placeholder="Parent, guardian, sibling" required></label><label class="form-label">Email Address<input class="input" name="next_of_kin_email" type="email" placeholder="guardian@example.com" required></label><label class="form-label">Phone Number<input class="input" name="next_of_kin_phone" type="tel" placeholder="08000000000" required><span class="form-help">Must be different from the student's email address and phone number.</span></label></div></div>
        <label class="form-label">Gender<select class="input" name="gender" required><option>Female</option><option>Male</option><option>Other</option></select></label>
        <label class="form-label">Faculty<select class="input" name="faculty_id" data-faculty-select required><option value="">Select faculty</option><?php foreach ($faculties as $faculty): ?><option value="<?= (int) $faculty['id'] ?>"><?= e($faculty['name']) ?></option><?php endforeach; ?></select></label>
        <label class="form-label">Department<select class="input" name="department_id" data-department-select required><option value="">Select faculty first</option></select></label>
        <label class="form-label">Programme Type<select class="input" name="programme_type" data-programme-select required><option>ND</option><option>HND</option></select></label>
        <label class="form-label">Level<select class="input" name="entry_level" data-level-select required><option>ND1</option><option>ND2</option><option>HND1</option><option>HND2</option></select><span class="form-help">ND1/HND1 are freshers. ND2/HND2 are staylites.</span></label>
        <label class="form-label">Study Mode<select class="input" name="study_mode" required><option>FT</option><option>PT</option></select></label>
        <label class="form-label">Admission Session<select class="input" name="admission_session_id" required><?php foreach ($sessions as $session): ?><option value="<?= (int) $session['id'] ?>" <?= $active && (int) $active['id'] === (int) $session['id'] ? 'selected' : '' ?>><?= e($session['name']) ?></option><?php endforeach; ?></select></label>
        <label class="form-label form-wide">Address<input class="input" name="address" placeholder="Residential address"></label>
        <label class="form-label password-field" data-password-field>Password<input class="input" name="password" type="password" autocomplete="new-password" required><button class="password-toggle" type="button" data-password-toggle aria-label="Show password" title="Show password"><span data-icon="eye"></span></button></label>
        <label class="form-label password-field" data-password-field>Confirm Password<input class="input" name="password_confirm" type="password" autocomplete="new-password" required><button class="password-toggle" type="button" data-password-toggle aria-label="Show password" title="Show password"><span data-icon="eye"></span></button></label>
      </div>
      <button class="btn-primary mt-6 w-full sm:w-auto" type="submit"><span data-icon="save"></span> Register Student</button>
    </form>
  </main>
  <?php student_public_mobile_nav('register'); ?>
</div>
<?php modal_and_toasts(); ?>
</body></html>
