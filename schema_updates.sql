-- ============================================================
-- Schema updates for Student Course Hub
-- Run this AFTER example_data.sql
-- Implements: visibility control, richer staff info, duplicate
-- prevention, unsubscribe tracking, image alt text, admin auth,
-- admin roles, and extra indexes for search performance.
-- ============================================================

USE student_course_hub;

-- 1. Controlling Programme Visibility (draft/published)
ALTER TABLE Programmes
    ADD COLUMN IsPublished BOOLEAN NOT NULL DEFAULT FALSE;

-- 2. Richer Staff Profiles
ALTER TABLE Staff
    ADD COLUMN JobTitle VARCHAR(150),
    ADD COLUMN Department VARCHAR(150),
    ADD COLUMN Bio TEXT,
    ADD COLUMN PhotoURL VARCHAR(255);

-- 3. Accessible images: alt text for programme/module images
ALTER TABLE Programmes
    ADD COLUMN ImageAltText VARCHAR(255);

ALTER TABLE Modules
    ADD COLUMN ImageAltText VARCHAR(255);

-- 4. Stopping duplicate student sign-ups
--    Prevent the same email registering interest in the same
--    programme twice.
ALTER TABLE InterestedStudents
    ADD CONSTRAINT uq_programme_email UNIQUE (ProgrammeID, Email);

-- 5. Letting students change their minds (soft unsubscribe)
ALTER TABLE InterestedStudents
    ADD COLUMN IsActive BOOLEAN NOT NULL DEFAULT TRUE;

-- 6. Securing the Admin Area
CREATE TABLE IF NOT EXISTS AdminRoles (
    RoleID INTEGER PRIMARY KEY AUTO_INCREMENT,
    RoleName VARCHAR(50) NOT NULL UNIQUE,
    CanManageProgrammes BOOLEAN NOT NULL DEFAULT FALSE,
    CanViewStudents BOOLEAN NOT NULL DEFAULT FALSE
);

INSERT INTO AdminRoles (RoleName, CanManageProgrammes, CanViewStudents) VALUES
    ('SuperAdmin', TRUE, TRUE),
    ('ProgrammeEditor', TRUE, FALSE),
    ('RecruitmentViewer', FALSE, TRUE);

CREATE TABLE IF NOT EXISTS Admins (
    AdminID INTEGER PRIMARY KEY AUTO_INCREMENT,
    Username VARCHAR(100) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    RoleID INTEGER NOT NULL DEFAULT 1,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (RoleID) REFERENCES AdminRoles(RoleID)
);

-- Default admin: username "admin", password "ChangeMe123!"
-- (hash generated with PHP password_hash — see includes/seed_admin.php
-- if you want to regenerate it)
INSERT INTO Admins (Username, PasswordHash, RoleID) VALUES
    ('admin', '$2b$10$lir7fn8cFcIGJTtNikakT.tDJmXTx1felm3AvJusVzhyTTAIVWbIG', 1);
-- Default login: username "admin", password "ChangeMe123!"
-- Change this by running: php includes/seed_admin.php "YourNewPassword"
-- and updating the PasswordHash column with the output.

-- 7. Improving Search Speed — extra indexes
CREATE INDEX idx_programme_name ON Programmes (ProgrammeName(100));
CREATE INDEX idx_programme_level ON Programmes (LevelID);
CREATE INDEX idx_programme_published ON Programmes (IsPublished);
CREATE INDEX idx_module_name ON Modules (ModuleName(100));
CREATE INDEX idx_interested_programme ON InterestedStudents (ProgrammeID);
CREATE INDEX idx_interested_active ON InterestedStudents (IsActive);

-- Mark existing seed programmes as published so the site has content
UPDATE Programmes SET IsPublished = TRUE;
