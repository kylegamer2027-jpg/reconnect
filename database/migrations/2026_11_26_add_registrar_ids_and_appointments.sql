ALTER TABLE users
    ADD COLUMN registrar_id VARCHAR(50) NULL AFTER student_id,
    ADD UNIQUE KEY uq_users_registrar_id (registrar_id);

UPDATE users
SET registrar_id = student_id
WHERE role = 'registrar'
  AND student_id REGEXP '^[0-9]+$';

UPDATE users
SET registrar_id = CONCAT('9', LPAD(id, 12, '0'))
WHERE role = 'registrar'
  AND registrar_id IS NULL;

CREATE TABLE appointments (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    purpose VARCHAR(500) NOT NULL,
    status ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_appointments_date_status (appointment_date, status),
    KEY idx_appointments_user_date (user_id, appointment_date),
    CONSTRAINT fk_appointments_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (student_id, full_name, email, password, role)
SELECT '99999999', 'Demo Student', 'demo.student@example.com',
       '$2y$10$uCuoKPJZdSCktmP9qgTrSum3SZVOTdg.mWOXe9E.oMNhv8elicmzO',
       'student'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1
    FROM users
    WHERE email = 'demo.student@example.com'
       OR student_id = '99999999'
);
