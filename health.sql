-- Drop tables in reverse order of dependency
DROP TABLE IF EXISTS Family_Member, Commissions, Payments, Customer_Policy, Feedback, Policies, Customers, Agents, Managers;

-- Managers Table
CREATE TABLE Managers (
    manager_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Agents Table (depends on Managers)
CREATE TABLE Agents (
    agent_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    Gender VARCHAR(255),
    commission_rate DECIMAL(5,2) NOT NULL, 
    expertise VARCHAR(255) NOT NULL,
    photo_url VARCHAR(255) DEFAULT NULL,
    manager_id INT, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES Managers(manager_id) 
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- Customers Table (depends on Agents and Managers)
CREATE TABLE Customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    Gender VARCHAR(255),
    password VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(15) NOT NULL,
    age INT NOT NULL,
    manager_id INT , 
    agent_id INT ,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES Agents(agent_id),
    FOREIGN KEY (manager_id) REFERENCES Managers(manager_id) 
       
);

-- Policies Table (depends on Managers)
CREATE TABLE Policies (
    policy_id INT AUTO_INCREMENT PRIMARY KEY,
    policy_name VARCHAR(255) NOT NULL,
    policy_type ENUM('personal', 'with family') NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    profit_rate DECIMAL(5,2) NOT NULL,
    policy_term INT NOT NULL,
    payment_interval ENUM('monthly', 'quarterly', 'half_annually', 'annually') NOT NULL,
    manager_id INT ,  
    FOREIGN KEY (manager_id) REFERENCES Managers(manager_id) 
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- Feedback Table (depends on Customers, Agents, Managers)
CREATE TABLE Feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    feedback_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    customer_id INT DEFAULT NULL,
    agent_id INT DEFAULT NULL,
    manager_id INT DEFAULT NULL,
    receiver ENUM('customer', 'agent', 'manager') NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES Customers(customer_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES Agents(agent_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES Managers(manager_id) 
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- Customer_Policy Table (many-to-many between Customers and Policies)
CREATE TABLE Customer_Policy (
    customer_id INT NOT NULL,
    policy_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    policy_name VARCHAR(255),
    customer_name VARCHAR(255),
    status ENUM('Active', 'Pending', 'Expired') NOT NULL,
    PRIMARY KEY (customer_id, policy_id),
    FOREIGN KEY (customer_id) REFERENCES Customers(customer_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (policy_id) REFERENCES Policies(policy_id) 
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- Payments Table (links Customers and Policies)
CREATE TABLE Payments (
    payment_number INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    policy_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    customer_name VARCHAR(255),
    policy_name VARCHAR(255),
    bank_name VARCHAR(255),
    account_number VARCHAR(255),
    status ENUM('Active', 'Pending', 'Expired') NOT NULL,
    proof_photo VARCHAR(255) DEFAULT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES Customers(customer_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (policy_id) REFERENCES Policies(policy_id) 
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- Commissions Table (links Payments and Agents)
CREATE TABLE Commissions (
    commission_number INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    policy_id INT NOT NULL,
    payment_number INT NOT NULL,
    commission_amount DECIMAL(10, 2) NOT NULL,
    commission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    agent_id INT NOT NULL,
    FOREIGN KEY (payment_number) REFERENCES Payments(payment_number) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES Agents(agent_id) 
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- Family_Member Table (related to Customers)
CREATE TABLE Family_Member (
    fam_no INT AUTO_INCREMENT PRIMARY KEY,  
    customer_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    age INT NOT NULL,
    relation ENUM('Husband', 'Wife', 'Son', 'Daughter') NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES Customers(customer_id) 
        ON DELETE CASCADE ON UPDATE CASCADE
);


