DROP DATABASE IF EXISTS diplomatiki_ergasia;
CREATE DATABASE diplomatiki_ergasia;
USE diplomatiki_ergasia;

CREATE TABLE IF NOT EXISTS students (
    id INT NOT NULL UNIQUE,
	name VARCHAR(50) DEFAULT ('Unknown'),               
    surname VARCHAR(50) DEFAULT ('Unknown'),  
	student_number INT NOT NULL,
	street VARCHAR(80) DEFAULT ('Unknown'),
	number VARCHAR(15) DEFAULT NULL,
	city VARCHAR(25) DEFAULT ('Unknown'),
	postcode INT DEFAULT NULL,
	father_name VARCHAR(25) DEFAULT ('Unknown'),       
    landline_telephone VARCHAR(15) DEFAULT ('Unknown'),
	mobile_telephone VARCHAR(15) DEFAULT ('Unknown'),
	email VARCHAR(100) DEFAULT ('Unknown'),
	
	PRIMARY KEY (student_number)
	
);

CREATE TABLE IF NOT EXISTS professors (
    id INT NOT NULL UNIQUE,
	name VARCHAR(50) DEFAULT ('Unknown'),               
    surname VARCHAR(50) DEFAULT ('Unknown'),   
	email VARCHAR(100) NOT NULL DEFAULT ('Unknown'),
    topic VARCHAR(100) DEFAULT ('Unknown'),     
    landline VARCHAR(15) DEFAULT ('Unknown'),
	mobile VARCHAR(15) DEFAULT ('Unknown'),   
	department VARCHAR(100) DEFAULT ('Unknown'),
	university VARCHAR(100) DEFAULT ('Unknown'),
	
	PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS secretariat (
	id INT NOT NULL UNIQUE,
	email VARCHAR(100) NOT NULL DEFAULT ('Unknown'),
	PRIMARY KEY (email)
);

CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'professor', 'secretariat') NOT NULL,
    PRIMARY KEY (email)
);

	

CREATE TABLE IF NOT EXISTS diplomatiki (
	id_diplomatikis INT AUTO_INCREMENT, 
	topic VARCHAR(200) DEFAULT ('Unknown'),
	description TEXT DEFAULT ('Unknown'),
	pdf TEXT DEFAULT NULL;
	state ENUM('active', 'under examination', 'finished', 'canceled', 'under assignment'),
	prof_num INT DEFAULT NULL,
	stud_num INT DEFAULT NULL,
	active_date DATETIME DEFAULT NULL,
	finished_date DATETIME DEFAULT NULL,
	announcement_text TEXT DEFAULT NULL,
	
	PRIMARY KEY (id_diplomatikis),
	
	FOREIGN KEY (prof_num) REFERENCES professors(id)
	ON UPDATE CASCADE ON DELETE CASCADE,

	FOREIGN KEY (stud_num) REFERENCES students(student_number)
	ON UPDATE CASCADE ON DELETE CASCADE	
);



CREATE TABLE IF NOT EXISTS commitee (
    id_diplomatikis INT NOT NULL UNIQUE,
    supervisor INT DEFAULT NULL,
    num_prof2 INT DEFAULT NULL,
    num_prof3 INT DEFAULT NULL,
    note1 TEXT DEFAULT NULL, 
    note2 TEXT DEFAULT NULL ,
    note3 TEXT DEFAULT NULL,
    supervisor_grade INT DEFAULT NULL, 
    grade2 INT DEFAULT NULL,
    grade3 INT DEFAULT NULL, 
    grade_registration_state ENUM('active', 'inactive') DEFAULT 'inactive',
	
    FOREIGN KEY (id_diplomatikis) REFERENCES diplomatiki(id_diplomatikis)
    ON UPDATE CASCADE ON DELETE CASCADE,

    FOREIGN KEY (supervisor) REFERENCES professors(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
    
    FOREIGN KEY (num_prof2) REFERENCES professors(id)
    ON UPDATE CASCADE ON DELETE CASCADE,

    FOREIGN KEY (num_prof3) REFERENCES professors(id)
    ON UPDATE CASCADE ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS commitee_app (
	stud_num INT DEFAULT NULL,
	id_diplomatikis INT DEFAULT NULL,
	prof_num_app INT DEFAULT NULL,
	invitation_date DATETIME DEFAULT NULL,
	acceptance_date DATETIME DEFAULT NULL,
	rejection_date DATETIME DEFAULT NULL
	
	FOREIGN KEY (stud_num) REFERENCES students(student_number)
	ON UPDATE CASCADE ON DELETE CASCADE,
	
	FOREIGN KEY (id_diplomatikis) REFERENCES diplomatiki(id_diplomatikis)
	ON UPDATE CASCADE ON DELETE CASCADE,
	
	FOREIGN KEY (prof_num_app) REFERENCES professors(id)
	ON UPDATE CASCADE ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS praktiko (
	id_diplomatikis INT DEFAULT NULL UNIQUE,
	grade INT DEFAULT NULL, 
	link TEXT DEFAULT NULL,
	
	FOREIGN KEY (id_diplomatikis) REFERENCES diplomatiki(id_diplomatikis)
	ON UPDATE CASCADE ON DELETE CASCADE
	
);

CREATE TABLE IF NOT EXISTS support_files(
	id_diplomatikis INT DEFAULT NULL UNIQUE,
	path_of_files TEXT DEFAULT NULL,
	link TEXT NOT NULL,
	examination_date DATETIME DEFAULT NULL,
	presentation ENUM('remote', 'live', 'UNKNOWN') DEFAULT 'UNKNOWN',
	remote_room TEXT DEFAULT NULL,
	room VARCHAR(40) DEFAULT NULL,
	
	FOREIGN KEY (id_diplomatikis) REFERENCES diplomatiki(id_diplomatikis)
	ON UPDATE CASCADE ON DELETE CASCADE
	
	
);

CREATE TABLE IF NOT EXISTS general_meetings (
        protocol_number INT NOT NULL PRIMARY KEY,
        general_meeting_date DATE NOT NULL, 
        reasoning TEXT DEFAULT NULL
);
