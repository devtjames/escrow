<?php
require_once __DIR__ . '/../app/bootstrap.php';
$facultyId = (int) field('faculty_id');
$programme = strtoupper(field('programme_type'));
if ($facultyId > 0 && in_array($programme, ['ND', 'HND'], true)) {
    $rows = db_all(
        'SELECT id, name, award_type FROM departments WHERE faculty_id = ? AND status = "active" AND (award_type = ? OR award_type = "HND/ND") ORDER BY name',
        [$facultyId, $programme]
    );
} else {
    $rows = $facultyId > 0
        ? db_all('SELECT id, name, award_type FROM departments WHERE faculty_id = ? AND status = "active" ORDER BY name', [$facultyId])
        : [];
}
json_response(['ok' => true, 'departments' => $rows]);
