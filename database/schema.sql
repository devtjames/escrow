CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(160) NOT NULL,
  staff_id VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_admin','admin') NOT NULL DEFAULT 'admin',
  status ENUM('pending','active','suspended') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS faculties (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL UNIQUE,
  dean_name VARCHAR(160) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS departments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  faculty_id INT UNSIGNED NOT NULL,
  name VARCHAR(160) NOT NULL,
  hod_name VARCHAR(160) NULL,
  award_type ENUM('ND','HND','HND/ND') NOT NULL DEFAULT 'HND/ND',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY departments_faculty_name_unique (faculty_id, name),
  CONSTRAINT departments_faculty_fk FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sessions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(20) NOT NULL UNIQUE,
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('open','closed','archived') NOT NULL DEFAULT 'open',
  start_date DATE NULL,
  end_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS semesters (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id INT UNSIGNED NOT NULL,
  name VARCHAR(80) NOT NULL,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT semesters_session_fk FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS students (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  next_of_kin_full_name VARCHAR(160) NOT NULL,
  next_of_kin_email VARCHAR(190) NOT NULL,
  next_of_kin_phone VARCHAR(40) NOT NULL,
  next_of_kin_relationship VARCHAR(100) NOT NULL,
  address TEXT NULL,
  gender ENUM('Male','Female','Other') NOT NULL,
  faculty_id INT UNSIGNED NOT NULL,
  department_id INT UNSIGNED NOT NULL,
  programme_type ENUM('ND','HND') NOT NULL,
  entry_level ENUM('ND1','ND2','HND1','HND2') NOT NULL DEFAULT 'ND1',
  study_mode ENUM('FT','PT') NOT NULL,
  application_number VARCHAR(80) NOT NULL UNIQUE,
  matric_number VARCHAR(80) NULL UNIQUE,
  matric_locked TINYINT(1) NOT NULL DEFAULT 0,
  admission_session_id INT UNSIGNED NOT NULL,
  status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT students_faculty_fk FOREIGN KEY (faculty_id) REFERENCES faculties(id),
  CONSTRAINT students_department_fk FOREIGN KEY (department_id) REFERENCES departments(id),
  CONSTRAINT students_admission_session_fk FOREIGN KEY (admission_session_id) REFERENCES sessions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fees (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  fee_type ENUM('school_fee','departmental_fee','acceptance_fee','institutional_charge','other') NOT NULL,
  amount INT UNSIGNED NOT NULL,
  faculty_id INT UNSIGNED NULL,
  department_id INT UNSIGNED NULL,
  session_id INT UNSIGNED NOT NULL,
  programme_type ENUM('ND','HND') NULL,
  study_mode ENUM('FT','PT') NULL,
  fee_level ENUM('ND1','ND2','HND1','HND2') NULL,
  student_category ENUM('Fresher','Staylite') NULL,
  status ENUM('active','draft','disabled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fees_faculty_fk FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE SET NULL,
  CONSTRAINT fees_department_fk FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  CONSTRAINT fees_session_fk FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wallets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  session_id INT UNSIGNED NOT NULL,
  target_amount INT UNSIGNED NOT NULL DEFAULT 0,
  current_balance INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('active','completed','paid') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY wallets_student_session_unique (student_id, session_id),
  CONSTRAINT wallets_student_fk FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT wallets_session_fk FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pending_payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  wallet_id INT UNSIGNED NOT NULL,
  reference VARCHAR(120) NOT NULL UNIQUE,
  amount INT UNSIGNED NOT NULL,
  gateway VARCHAR(40) NOT NULL DEFAULT 'Paystack',
  status ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT pending_student_fk FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT pending_wallet_fk FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  wallet_id INT UNSIGNED NOT NULL,
  session_id INT UNSIGNED NOT NULL,
  reference VARCHAR(120) NOT NULL UNIQUE,
  gateway VARCHAR(40) NOT NULL,
  amount INT UNSIGNED NOT NULL,
  status ENUM('pending','successful','failed') NOT NULL DEFAULT 'pending',
  receipt_email_sent_at DATETIME NULL,
  receipt_email_error TEXT NULL,
  next_of_kin_receipt_email_sent_at DATETIME NULL,
  next_of_kin_receipt_email_error TEXT NULL,
  payload_json JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT transactions_student_fk FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT transactions_wallet_fk FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
  CONSTRAINT transactions_session_fk FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  wallet_id INT UNSIGNED NOT NULL,
  session_id INT UNSIGNED NOT NULL,
  transaction_reference VARCHAR(120) NOT NULL,
  fee_type VARCHAR(80) NOT NULL,
  amount INT UNSIGNED NOT NULL,
  status ENUM('pending','successful','failed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT payments_student_fk FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT payments_wallet_fk FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
  CONSTRAINT payments_session_fk FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT resets_student_fk FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS email_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recipient VARCHAR(190) NOT NULL,
  subject VARCHAR(190) NOT NULL,
  body MEDIUMTEXT NOT NULL,
  status ENUM('sent','failed') NOT NULL,
  error_message TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL UNIQUE,
  setting_value TEXT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED NULL,
  student_id INT UNSIGNED NULL,
  action VARCHAR(160) NOT NULL,
  context_json JSON NULL,
  ip_address VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  scope ENUM('student','admin') NOT NULL,
  identifier VARCHAR(190) NOT NULL,
  ip_address VARCHAR(64) NOT NULL,
  successful TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX login_attempts_lookup (scope, identifier, ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS paystack_webhooks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_name VARCHAR(120) NOT NULL,
  reference VARCHAR(120) NULL,
  payload_json JSON NOT NULL,
  processed TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY webhook_reference_event_unique (reference, event_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO sessions (id, name, is_active, status, start_date, end_date)
VALUES (1, '2025/2026', 1, 'open', '2025-09-01', '2026-08-31');

INSERT IGNORE INTO faculties (id, name, dean_name, status) VALUES
(1, 'Applied Sciences', 'Prof. A. Salami', 'active'),
(2, 'Business and Management', 'Dr. R. Bello', 'active'),
(3, 'Communication and Information', 'Dr. H. Musa', 'active');

INSERT IGNORE INTO departments (id, faculty_id, name, award_type, status) VALUES
(1, 1, 'Computer Science', 'HND/ND', 'active'),
(2, 1, 'Statistics', 'ND', 'active'),
(3, 2, 'Accountancy', 'HND/ND', 'active'),
(4, 3, 'Mass Communication', 'HND/ND', 'active');
