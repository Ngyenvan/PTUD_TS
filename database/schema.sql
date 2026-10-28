-- IS4 MVC – mô hình CSDL demo có ràng buộc, độc lập với dự án cũ.
-- KHÔNG DROP DATABASE / TRUNCATE. Dùng utf8mb4 trên MariaDB/MySQL.
CREATE TABLE IF NOT EXISTS roles (
 id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(32) NOT NULL UNIQUE, label VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS permissions (
 id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(60) NOT NULL UNIQUE, label VARCHAR(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS role_permissions (
 role_id INT NOT NULL, permission_id INT NOT NULL, PRIMARY KEY(role_id,permission_id),
 FOREIGN KEY(role_id) REFERENCES roles(id), FOREIGN KEY(permission_id) REFERENCES permissions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS schools (
 id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(40) NOT NULL UNIQUE, name VARCHAR(180) NOT NULL,
 type ENUM('THCS','THPT') NOT NULL, district VARCHAR(90) NOT NULL, address VARCHAR(255) NULL,
 description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS exam_cycles (
 id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(40) NOT NULL UNIQUE, name VARCHAR(150) NOT NULL,
 year SMALLINT NOT NULL, register_from DATETIME NOT NULL, register_to DATETIME NOT NULL,
 edit_from DATETIME NOT NULL, edit_to DATETIME NOT NULL, exam_date DATE NOT NULL,
 status ENUM('draft','open','closed','approved','published') NOT NULL DEFAULT 'draft',
 published_at DATETIME NULL, CONSTRAINT chk_cycle_dates CHECK(register_from<register_to AND edit_from<edit_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS users (
 id INT AUTO_INCREMENT PRIMARY KEY, login VARCHAR(80) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL,
 full_name VARCHAR(140) NOT NULL, role_id INT NOT NULL, school_id INT NULL, exam_id INT NULL,
 enabled TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (role_id) REFERENCES roles(id), FOREIGN KEY(school_id) REFERENCES schools(id),
 FOREIGN KEY(exam_id) REFERENCES exam_cycles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS user_permission_overrides (
 user_id INT NOT NULL, permission_id INT NOT NULL, effect ENUM('grant','revoke') NOT NULL,
 updated_by INT NOT NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(user_id,permission_id),
 FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(permission_id) REFERENCES permissions(id),
 FOREIGN KEY(updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS candidates (
 id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(40) NOT NULL UNIQUE, full_name VARCHAR(140) NOT NULL,
 birth_date DATE NOT NULL, thcs_id INT NOT NULL, user_id INT NULL UNIQUE,
 profile_status ENUM('pending','valid','needs_fix') NOT NULL DEFAULT 'pending',
 FOREIGN KEY(thcs_id) REFERENCES schools(id), FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS quotas (
 id INT AUTO_INCREMENT PRIMARY KEY, exam_id INT NOT NULL, school_id INT NOT NULL, seats INT NOT NULL,
 UNIQUE(exam_id,school_id), FOREIGN KEY(exam_id) REFERENCES exam_cycles(id),
 FOREIGN KEY(school_id) REFERENCES schools(id), CONSTRAINT chk_quota CHECK(seats>=0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS preferences (
 id INT AUTO_INCREMENT PRIMARY KEY, exam_id INT NOT NULL, candidate_id INT NOT NULL, school_id INT NOT NULL,
 priority TINYINT NOT NULL, UNIQUE(exam_id,candidate_id,priority), UNIQUE(exam_id,candidate_id,school_id),
 FOREIGN KEY(exam_id) REFERENCES exam_cycles(id), FOREIGN KEY(candidate_id) REFERENCES candidates(id),
 FOREIGN KEY(school_id) REFERENCES schools(id), CONSTRAINT chk_priority CHECK(priority BETWEEN 1 AND 3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS rooms (
 id INT AUTO_INCREMENT PRIMARY KEY, exam_id INT NOT NULL, school_id INT NOT NULL, code VARCHAR(40) NOT NULL,
 capacity INT NOT NULL, UNIQUE(exam_id,code), FOREIGN KEY(exam_id) REFERENCES exam_cycles(id),
 FOREIGN KEY(school_id) REFERENCES schools(id), CONSTRAINT chk_room_capacity CHECK(capacity>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS allocations (
 id INT AUTO_INCREMENT PRIMARY KEY, exam_id INT NOT NULL, room_id INT NOT NULL,
 candidate_id INT NOT NULL, candidate_number VARCHAR(24) NOT NULL,
 UNIQUE(exam_id,candidate_id), UNIQUE(exam_id,candidate_number),
 FOREIGN KEY(exam_id) REFERENCES exam_cycles(id), FOREIGN KEY(room_id) REFERENCES rooms(id),
 FOREIGN KEY(candidate_id) REFERENCES candidates(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS exam_papers (
 id INT AUTO_INCREMENT PRIMARY KEY, allocation_id INT NOT NULL, subject ENUM('Ngữ văn','Ngoại ngữ','Toán') NOT NULL,
 blind_code VARCHAR(28) NULL UNIQUE, package_code VARCHAR(28) NULL,
 blind_status ENUM('new','numbered','checked','sealed','handed') NOT NULL DEFAULT 'new',
 score DECIMAL(4,2) NULL, score_basis VARCHAR(255) NULL,
 UNIQUE(allocation_id,subject), FOREIGN KEY(allocation_id) REFERENCES allocations(id),
 CONSTRAINT chk_score CHECK(score IS NULL OR (score>=0 AND score<=10))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS eligibility (
 id INT AUTO_INCREMENT PRIMARY KEY, exam_id INT NOT NULL, candidate_id INT NOT NULL,
 status ENUM('pending','eligible','ineligible') NOT NULL DEFAULT 'pending',
 priority_bonus DECIMAL(5,2) NOT NULL DEFAULT 0.00, encouragement_bonus DECIMAL(5,2) NOT NULL DEFAULT 0.00,
 note VARCHAR(255) NULL, reviewed_by INT NULL, UNIQUE(exam_id,candidate_id),
 FOREIGN KEY(exam_id) REFERENCES exam_cycles(id), FOREIGN KEY(candidate_id) REFERENCES candidates(id),
 FOREIGN KEY(reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS cutoffs (
 id INT AUTO_INCREMENT PRIMARY KEY, exam_id INT NOT NULL, school_id INT NOT NULL, priority TINYINT NOT NULL,
 threshold DECIMAL(5,2) NOT NULL, confirmed_by INT NOT NULL, confirmed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE(exam_id,school_id,priority), FOREIGN KEY(exam_id) REFERENCES exam_cycles(id),
 FOREIGN KEY(school_id) REFERENCES schools(id), FOREIGN KEY(confirmed_by) REFERENCES users(id),
 CONSTRAINT chk_cutoff_priority CHECK(priority BETWEEN 1 AND 3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS admissions (
 id INT AUTO_INCREMENT PRIMARY KEY, exam_id INT NOT NULL, candidate_id INT NOT NULL,
 school_id INT NULL, priority TINYINT NULL, score DECIMAL(5,2) NULL,
 status ENUM('accepted','rejected') NOT NULL, confirmed_by INT NOT NULL,
 confirmed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE(exam_id,candidate_id), FOREIGN KEY(exam_id) REFERENCES exam_cycles(id),
 FOREIGN KEY(candidate_id) REFERENCES candidates(id), FOREIGN KEY(school_id) REFERENCES schools(id),
 FOREIGN KEY(confirmed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS audit_logs (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, actor_id INT NULL, action VARCHAR(90) NOT NULL,
 details TEXT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(actor_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS announcements (
 id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(250) NOT NULL, body TEXT NOT NULL,
 posted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
