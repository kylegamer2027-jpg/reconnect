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
