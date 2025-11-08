-- MySQL script to create database, table, and insert a user
CREATE DATABASE IF NOT EXISTS project_db;
USE project_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS board_passers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) DEFAULT '',
    last_name VARCHAR(100) NOT NULL,
    sex VARCHAR(10) DEFAULT NULL,
    course VARCHAR(255) NOT NULL,
    year_graduated INT NOT NULL,
    board_exam_date DATE NOT NULL,
    result VARCHAR(10) NOT NULL,
    department VARCHAR(100) NOT NULL,
    exam_type VARCHAR(20) DEFAULT 'First Timer',
    board_exam_type VARCHAR(100) DEFAULT 'Registered Electrical Engineer Licensure Exam (REELE)'
);

CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL
);

-- Sample engineering courses
INSERT INTO courses (course_name, department) VALUES ('Bachelor of Science in Electronics Engineering (BSECE)', 'Engineering');
INSERT INTO courses (course_name, department) VALUES ('Bachelor of Science in Electrical Engineering (BSEE)', 'Engineering');
INSERT INTO courses (course_name, department) VALUES ('Bachelor of Science in Computer Engineering (BSCpE)', 'Engineering');

INSERT INTO users (email, password) VALUES ('yourgmail@gmail.com', 'adminpass');
INSERT INTO users (email, password) VALUES ('angel@gmail.com', '12345');
INSERT INTO users (email, password) VALUES ('eng_admin@lspu.edu.ph', 'engpass');
INSERT INTO users (email, password) VALUES ('cas_admin@lspu.edu.ph', 'caspass');
INSERT INTO users (email, password) VALUES ('cbaa_admin@lspu.edu.ph', 'cbaapass');
INSERT INTO users (email, password) VALUES ('ccje_admin@lspu.edu.ph', 'ccjepass');
INSERT INTO users (email, password) VALUES ('cte_admin@lspu.edu.ph', 'ctepass');
INSERT INTO users (email, password) VALUES ('icts_admin@lspu.edu.ph', 'ictspass');
INSERT INTO users (email, password) VALUES ('president@lspu.edu.ph', 'prespass');
