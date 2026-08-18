CREATE TABLE IF NOT EXISTS specialties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    status TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    status TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS insurance_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    status TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    status TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS providers (
    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(255) NOT NULL,

    provider_type VARCHAR(100) DEFAULT 'Specialist',

    specialty_id INT NULL,

    description TEXT,

    gender ENUM('Male','Female','Other') DEFAULT NULL,

    address VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(100),
    zip VARCHAR(20),

    phone VARCHAR(50),
    email VARCHAR(255),

    area_id INT NULL,

    accepting_new_patients TINYINT(1) DEFAULT 0,

    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,

    status TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (specialty_id)
        REFERENCES specialties(id)
        ON DELETE SET NULL,

    FOREIGN KEY (area_id)
        REFERENCES areas(id)
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS provider_languages (
    provider_id INT NOT NULL,
    language_id INT NOT NULL,

    PRIMARY KEY (provider_id, language_id),

    FOREIGN KEY (provider_id)
        REFERENCES providers(id)
        ON DELETE CASCADE,

    FOREIGN KEY (language_id)
        REFERENCES languages(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS provider_insurance (
    provider_id INT NOT NULL,
    insurance_id INT NOT NULL,

    PRIMARY KEY (provider_id, insurance_id),

    FOREIGN KEY (provider_id)
        REFERENCES providers(id)
        ON DELETE CASCADE,

    FOREIGN KEY (insurance_id)
        REFERENCES insurance_plans(id)
        ON DELETE CASCADE
);