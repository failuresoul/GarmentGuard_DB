DROP TABLE SAFETY_ALERT CASCADE CONSTRAINTS;
DROP TABLE ERROR_LOG CASCADE CONSTRAINTS;
DROP TABLE SALARY_RECORD CASCADE CONSTRAINTS;
DROP TABLE GRIEVANCE_AUDIT_LOG CASCADE CONSTRAINTS;
DROP TABLE GRIEVANCE CASCADE CONSTRAINTS;
DROP TABLE SAFETY_EQUIPMENT CASCADE CONSTRAINTS;
DROP TABLE CERTIFICATION CASCADE CONSTRAINTS;
DROP TABLE AUDIT CASCADE CONSTRAINTS;
DROP TABLE WORKER CASCADE CONSTRAINTS;
DROP TABLE USER_ CASCADE CONSTRAINTS;
DROP TABLE BUYER_FACTORY CASCADE CONSTRAINTS;
DROP TABLE BUYER CASCADE CONSTRAINTS;
DROP TABLE FACTORY CASCADE CONSTRAINTS;

CREATE TABLE FACTORY (
  factory_id NUMBER PRIMARY KEY,
  factory_name VARCHAR2(100) NOT NULL,
  registration_no VARCHAR2(50) NOT NULL UNIQUE,
  address VARCHAR2(200) NOT NULL,
  district VARCHAR2(50) NOT NULL,
  division VARCHAR2(50) NOT NULL,
  total_workers NUMBER DEFAULT 0,
  compliance_status VARCHAR2(20) DEFAULT 'Pending',
  compliance_score NUMBER(5,2) DEFAULT 0,
  last_audit_date DATE,
  next_audit_date DATE,
  contact_person VARCHAR2(100) NOT NULL,
  phone VARCHAR2(20),
  email VARCHAR2(100) NOT NULL,
  CONSTRAINT chk_compliance_score CHECK (compliance_score BETWEEN 0 AND 100),
  CONSTRAINT chk_factory_status CHECK (compliance_status IN ('Pending','Compliant','At Risk','Non-Compliant','Review Needed'))
);

CREATE TABLE WORKER (
  worker_id NUMBER PRIMARY KEY,
  factory_id NUMBER NOT NULL REFERENCES FACTORY(factory_id),
  full_name VARCHAR2(100) NOT NULL,
  national_id VARCHAR2(20) NOT NULL UNIQUE,
  designation VARCHAR2(50) NOT NULL,
  join_date DATE NOT NULL,
  base_salary NUMBER(10,2) NOT NULL,
  shift VARCHAR2(20),
  status VARCHAR2(20) DEFAULT 'Active',
  phone VARCHAR2(20),
  email VARCHAR2(100),
  CONSTRAINT chk_base_salary CHECK (base_salary > 0),
  CONSTRAINT chk_worker_status CHECK (status IN ('Active','Inactive','Terminated'))
);

CREATE TABLE USER_ (
  user_id NUMBER PRIMARY KEY,
  username VARCHAR2(50) NOT NULL UNIQUE,
  password_hash VARCHAR2(255) NOT NULL,
  role VARCHAR2(30) NOT NULL,
  full_name VARCHAR2(100) NOT NULL,
  factory_id NUMBER REFERENCES FACTORY(factory_id),
  email VARCHAR2(100) NOT NULL UNIQUE,
  status VARCHAR2(20) DEFAULT 'Active',
  CONSTRAINT chk_user_role CHECK (role IN ('admin','compliance_officer','inspector','buyer_user','worker'))
);

CREATE TABLE AUDIT (
  audit_id NUMBER PRIMARY KEY,
  factory_id NUMBER NOT NULL REFERENCES FACTORY(factory_id),
  inspector_id NUMBER REFERENCES USER_(user_id),
  audit_date DATE NOT NULL,
  next_scheduled DATE,
  score NUMBER(5,2),
  result VARCHAR2(20),
  findings CLOB,
  recommendations CLOB,
  CONSTRAINT chk_audit_score CHECK (score BETWEEN 0 AND 100),
  CONSTRAINT chk_audit_result CHECK (result IN ('Pass','Fail','Pending'))
);

CREATE TABLE CERTIFICATION (
  cert_id NUMBER PRIMARY KEY,
  factory_id NUMBER NOT NULL REFERENCES FACTORY(factory_id),
  cert_name VARCHAR2(100) NOT NULL,
  issuing_body VARCHAR2(100),
  issue_date DATE,
  expiry_date DATE,
  status VARCHAR2(20) DEFAULT 'Active',
  CONSTRAINT chk_cert_status CHECK (status IN ('Active','Expired','Revoked'))
);

CREATE TABLE SAFETY_EQUIPMENT (
  equipment_id NUMBER PRIMARY KEY,
  factory_id NUMBER NOT NULL REFERENCES FACTORY(factory_id),
  equipment_type VARCHAR2(100) NOT NULL,
  quantity NUMBER DEFAULT 0,
  purchase_date DATE,
  expiry_date DATE,
  last_inspection DATE,
  condition_status VARCHAR2(20) DEFAULT 'Good',
  location VARCHAR2(100),
  CONSTRAINT chk_condition CHECK (condition_status IN ('Good','Fair','Poor','Critical'))
);

CREATE TABLE GRIEVANCE (
  grievance_id NUMBER PRIMARY KEY,
  worker_id NUMBER NOT NULL REFERENCES WORKER(worker_id),
  category VARCHAR2(50) NOT NULL,
  description CLOB NOT NULL,
  submitted_date DATE DEFAULT SYSDATE,
  status VARCHAR2(20) DEFAULT 'Open',
  resolved_date DATE,
  resolution_notes CLOB,
  CONSTRAINT chk_grievance_status CHECK (status IN ('Open','In Progress','Resolved'))
);

CREATE TABLE GRIEVANCE_AUDIT_LOG (
  log_id NUMBER PRIMARY KEY,
  grievance_id NUMBER NOT NULL REFERENCES GRIEVANCE(grievance_id),
  old_status VARCHAR2(20),
  new_status VARCHAR2(20),
  changed_by VARCHAR2(100),
  changed_at DATE DEFAULT SYSDATE
);

CREATE TABLE SALARY_RECORD (
  record_id NUMBER PRIMARY KEY,
  worker_id NUMBER NOT NULL REFERENCES WORKER(worker_id),
  month NUMBER(2) NOT NULL,
  year NUMBER(4) NOT NULL,
  base_amount NUMBER(10,2) NOT NULL,
  overtime_hours NUMBER(5,2) DEFAULT 0,
  overtime_paid NUMBER(10,2) DEFAULT 0,
  deductions NUMBER(10,2) DEFAULT 0,
  net_salary NUMBER(10,2),
  payment_status VARCHAR2(20) DEFAULT 'Pending',
  CONSTRAINT chk_month CHECK (month BETWEEN 1 AND 12),
  CONSTRAINT chk_ot_hours CHECK (overtime_hours BETWEEN 0 AND 60),
  CONSTRAINT chk_salary_status CHECK (payment_status IN ('Pending','Paid'))
);

CREATE TABLE BUYER (
  buyer_id NUMBER PRIMARY KEY,
  buyer_name VARCHAR2(100) NOT NULL,
  country VARCHAR2(50),
  contact_name VARCHAR2(100),
  email VARCHAR2(100),
  phone VARCHAR2(20),
  brand_name VARCHAR2(100)
);

CREATE TABLE BUYER_FACTORY (
  buyer_id NUMBER NOT NULL REFERENCES BUYER(buyer_id),
  factory_id NUMBER NOT NULL REFERENCES FACTORY(factory_id),
  since_date DATE,
  contract_status VARCHAR2(20) DEFAULT 'Active',
  PRIMARY KEY (buyer_id, factory_id)
);

CREATE TABLE SAFETY_ALERT (
  alert_id NUMBER PRIMARY KEY,
  factory_id NUMBER NOT NULL REFERENCES FACTORY(factory_id),
  equipment_id NUMBER REFERENCES SAFETY_EQUIPMENT(equipment_id),
  alert_type VARCHAR2(50),
  alert_date DATE DEFAULT SYSDATE,
  is_acknowledged CHAR(1) DEFAULT 'N'
);

CREATE TABLE ERROR_LOG (
  log_id NUMBER PRIMARY KEY,
  proc_name VARCHAR2(100),
  error_code NUMBER,
  error_message VARCHAR2(500),
  logged_at DATE DEFAULT SYSDATE
);

INSERT INTO FACTORY (factory_id, factory_name, registration_no, address, district, division, total_workers, compliance_status, compliance_score, last_audit_date, next_audit_date, contact_person, phone, email)
VALUES (1, 'Dhaka Garments Ltd.', 'REG-001', 'Mirpur, Dhaka', 'Dhaka', 'Dhaka', 3, 'Compliant', 88.00, TO_DATE('2026-01-15', 'YYYY-MM-DD'), TO_DATE('2026-07-15', 'YYYY-MM-DD'), 'Rahim Uddin', '01711111111', 'dhaka@factory.com');

INSERT INTO FACTORY (factory_id, factory_name, registration_no, address, district, division, total_workers, compliance_status, compliance_score, last_audit_date, next_audit_date, contact_person, phone, email)
VALUES (2, 'Gazipur Fashion', 'REG-002', 'Chowrasta, Gazipur', 'Gazipur', 'Dhaka', 3, 'Review Needed', 62.00, TO_DATE('2026-02-10', 'YYYY-MM-DD'), TO_DATE('2026-08-10', 'YYYY-MM-DD'), 'Kamal Hossain', '01722222222', 'gazipur@factory.com');

INSERT INTO FACTORY (factory_id, factory_name, registration_no, address, district, division, total_workers, compliance_status, compliance_score, last_audit_date, next_audit_date, contact_person, phone, email)
VALUES (3, 'Chittagong Apparels', 'REG-003', 'EPZ, Chittagong', 'Chittagong', 'Chittagong', 3, 'Non-Compliant', 38.00, TO_DATE('2026-03-05', 'YYYY-MM-DD'), TO_DATE('2026-06-05', 'YYYY-MM-DD'), 'Nasir Uddin', '01733333333', 'chittagong@factory.com');

INSERT INTO FACTORY (factory_id, factory_name, registration_no, address, district, division, total_workers, compliance_status, compliance_score, last_audit_date, next_audit_date, contact_person, phone, email)
VALUES (4, 'Sylhet Textiles', 'REG-004', 'Khadim Nagar, Sylhet', 'Sylhet', 'Sylhet', 3, 'Compliant', 75.00, TO_DATE('2026-04-12', 'YYYY-MM-DD'), TO_DATE('2026-10-12', 'YYYY-MM-DD'), 'Sarker Alam', '01744444444', 'sylhet@factory.com');

INSERT INTO USER_ (user_id, username, password_hash, role, full_name, factory_id, email, status)
VALUES (1, 'admin_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXCNnZG6W', 'admin', 'System Administrator', NULL, 'admin@garmentguard.com', 'Active');

INSERT INTO USER_ (user_id, username, password_hash, role, full_name, factory_id, email, status)
VALUES (2, 'compliance_officer_1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXCNnZG6W', 'compliance_officer', 'Rahman Compliance', 1, 'compliance@dhaka.com', 'Active');

INSERT INTO USER_ (user_id, username, password_hash, role, full_name, factory_id, email, status)
VALUES (3, 'inspector_1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXCNnZG6W', 'inspector', 'Audit Inspector', NULL, 'inspector@garmentguard.com', 'Active');

INSERT INTO USER_ (user_id, username, password_hash, role, full_name, factory_id, email, status)
VALUES (4, 'buyer_user_1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXCNnZG6W', 'buyer_user', 'Buyer Representative', NULL, 'buyer@hm.com', 'Active');

INSERT INTO USER_ (user_id, username, password_hash, role, full_name, factory_id, email, status)
VALUES (5, 'worker_user_1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXCNnZG6W', 'worker', 'Worker User', 2, 'worker_user@gazipur.com', 'Active');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (1, 1, 'Abul Kashem', 'NID-10001', 'Sewing Operator', TO_DATE('2024-05-10', 'YYYY-MM-DD'), 12500.00, 'Day', 'Active', '01711111121', 'kashem@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (2, 1, 'Babul Mia', 'NID-10002', 'Quality Inspector', TO_DATE('2024-06-12', 'YYYY-MM-DD'), 14000.00, 'Day', 'Active', '01711111122', 'babul@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (3, 1, 'Champa Akter', 'NID-10003', 'Helper', TO_DATE('2025-01-02', 'YYYY-MM-DD'), 10000.00, 'Night', 'Active', '01711111123', 'champa@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (4, 2, 'Delwar Hossain', 'NID-10004', 'Sewing Operator', TO_DATE('2023-11-20', 'YYYY-MM-DD'), 13000.00, 'Day', 'Active', '01711111124', 'delwar@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (5, 2, 'Esha Khatun', 'NID-10005', 'Cutting Master', TO_DATE('2024-02-15', 'YYYY-MM-DD'), 16000.00, 'Day', 'Active', '01711111125', 'esha@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (6, 2, 'Faruk Ahmed', 'NID-10006', 'Helper', TO_DATE('2025-03-01', 'YYYY-MM-DD'), 9800.00, 'Night', 'Active', '01711111126', 'faruk@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (7, 3, 'Gias Uddin', 'NID-10007', 'Sewing Operator', TO_DATE('2024-01-10', 'YYYY-MM-DD'), 12800.00, 'Day', 'Active', '01711111127', 'gias@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (8, 3, 'Hena Begum', 'NID-10008', 'Ironman', TO_DATE('2024-04-18', 'YYYY-MM-DD'), 11500.00, 'Day', 'Active', '01711111128', 'hena@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (9, 3, 'Imran Khan', 'NID-10009', 'Packer', TO_DATE('2024-09-05', 'YYYY-MM-DD'), 11000.00, 'Night', 'Active', '01711111129', 'imran@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (10, 4, 'Jamil Hasan', 'NID-10010', 'Sewing Operator', TO_DATE('2024-08-12', 'YYYY-MM-DD'), 12700.00, 'Day', 'Active', '01711111130', 'jamil@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (11, 4, 'Kohinoor Akter', 'NID-10011', 'Line Chief', TO_DATE('2023-05-01', 'YYYY-MM-DD'), 18000.00, 'Day', 'Active', '01711111131', 'kohinoor@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (12, 4, 'Latifur Rahman', 'NID-10012', 'Helper', TO_DATE('2025-02-10', 'YYYY-MM-DD'), 10200.00, 'Night', 'Active', '01711111132', 'latifur@factory.com');

INSERT INTO AUDIT (audit_id, factory_id, inspector_id, audit_date, next_scheduled, score, result, findings, recommendations)
VALUES (1, 1, 3, TO_DATE('2026-01-15', 'YYYY-MM-DD'), TO_DATE('2026-07-15', 'YYYY-MM-DD'), 88.00, 'Pass', 'Good working conditions, minor safety issues resolved.', 'Maintain current standards.');

INSERT INTO AUDIT (audit_id, factory_id, inspector_id, audit_date, next_scheduled, score, result, findings, recommendations)
VALUES (2, 2, 3, TO_DATE('2026-02-10', 'YYYY-MM-DD'), TO_DATE('2026-08-10', 'YYYY-MM-DD'), 62.00, 'Pass', 'Some exits blocked, fire extinguishers need service.', 'Clear exits and service all safety equipment.');

INSERT INTO AUDIT (audit_id, factory_id, inspector_id, audit_date, next_scheduled, score, result, findings, recommendations)
VALUES (3, 3, 3, TO_DATE('2026-03-05', 'YYYY-MM-DD'), TO_DATE('2026-06-05', 'YYYY-MM-DD'), 38.00, 'Fail', 'Multiple safety violations, no fire drill logs, structural concerns.', 'Conduct immediate repairs, perform fire drills, and re-audit.');

INSERT INTO AUDIT (audit_id, factory_id, inspector_id, audit_date, next_scheduled, score, result, findings, recommendations)
VALUES (4, 4, 3, TO_DATE('2026-04-12', 'YYYY-MM-DD'), TO_DATE('2026-10-12', 'YYYY-MM-DD'), 75.00, 'Pass', 'Satisfactory compliance, minor ventilation issues.', 'Improve ventilation in the cutting area.');

INSERT INTO BUYER (buyer_id, buyer_name, country, contact_name, email, phone, brand_name)
VALUES (1, 'H&M', 'Sweden', 'Anna Larsson', 'anna@hm.com', '+4681234567', 'H&M');

INSERT INTO BUYER (buyer_id, buyer_name, country, contact_name, email, phone, brand_name)
VALUES (2, 'Walmart', 'USA', 'John Smith', 'john.smith@walmart.com', '+1479123456', 'Walmart');

INSERT INTO BUYER_FACTORY (buyer_id, factory_id, since_date, contract_status)
VALUES (1, 1, TO_DATE('2024-01-01', 'YYYY-MM-DD'), 'Active');

INSERT INTO BUYER_FACTORY (buyer_id, factory_id, since_date, contract_status)
VALUES (1, 2, TO_DATE('2024-06-15', 'YYYY-MM-DD'), 'Active');

INSERT INTO BUYER_FACTORY (buyer_id, factory_id, since_date, contract_status)
VALUES (2, 3, TO_DATE('2023-09-10', 'YYYY-MM-DD'), 'Active');

INSERT INTO BUYER_FACTORY (buyer_id, factory_id, since_date, contract_status)
VALUES (2, 4, TO_DATE('2025-02-01', 'YYYY-MM-DD'), 'Active');

INSERT INTO CERTIFICATION (cert_id, factory_id, cert_name, issuing_body, issue_date, expiry_date, status)
VALUES (1, 1, 'OEKO-TEX Standard 100', 'OEKO-TEX Association', TO_DATE('2025-05-10', 'YYYY-MM-DD'), TO_DATE('2027-05-10', 'YYYY-MM-DD'), 'Active');

INSERT INTO CERTIFICATION (cert_id, factory_id, cert_name, issuing_body, issue_date, expiry_date, status)
VALUES (2, 2, 'BSCI Code of Conduct', 'amfori', TO_DATE('2025-08-20', 'YYYY-MM-DD'), TO_DATE('2026-08-20', 'YYYY-MM-DD'), 'Active');

INSERT INTO CERTIFICATION (cert_id, factory_id, cert_name, issuing_body, issue_date, expiry_date, status)
VALUES (3, 3, 'ISO 9001:2015', 'SGS', TO_DATE('2024-02-15', 'YYYY-MM-DD'), TO_DATE('2025-02-15', 'YYYY-MM-DD'), 'Expired');

INSERT INTO CERTIFICATION (cert_id, factory_id, cert_name, issuing_body, issue_date, expiry_date, status)
VALUES (4, 4, 'WRAP Certification', 'WRAP', TO_DATE('2025-11-01', 'YYYY-MM-DD'), TO_DATE('2026-11-01', 'YYYY-MM-DD'), 'Active');

INSERT INTO SAFETY_EQUIPMENT (equipment_id, factory_id, equipment_type, quantity, purchase_date, expiry_date, last_inspection, condition_status, location)
VALUES (1, 1, 'Fire Extinguisher CO2', 50, TO_DATE('2024-06-01', 'YYYY-MM-DD'), TO_DATE('2026-07-20', 'YYYY-MM-DD'), TO_DATE('2026-01-10', 'YYYY-MM-DD'), 'Good', 'All Floors');

INSERT INTO SAFETY_EQUIPMENT (equipment_id, factory_id, equipment_type, quantity, purchase_date, expiry_date, last_inspection, condition_status, location)
VALUES (2, 2, 'Fire Hose Reel', 10, TO_DATE('2023-08-15', 'YYYY-MM-DD'), TO_DATE('2028-08-15', 'YYYY-MM-DD'), TO_DATE('2026-02-12', 'YYYY-MM-DD'), 'Good', 'Ground Floor');

INSERT INTO SAFETY_EQUIPMENT (equipment_id, factory_id, equipment_type, quantity, purchase_date, expiry_date, last_inspection, condition_status, location)
VALUES (3, 3, 'First Aid Kit', 15, TO_DATE('2025-07-28', 'YYYY-MM-DD'), TO_DATE('2026-07-28', 'YYYY-MM-DD'), TO_DATE('2026-03-01', 'YYYY-MM-DD'), 'Fair', 'Production Area');

INSERT INTO SAFETY_EQUIPMENT (equipment_id, factory_id, equipment_type, quantity, purchase_date, expiry_date, last_inspection, condition_status, location)
VALUES (4, 4, 'Smoke Detector', 120, TO_DATE('2024-11-12', 'YYYY-MM-DD'), TO_DATE('2029-11-12', 'YYYY-MM-DD'), TO_DATE('2026-04-10', 'YYYY-MM-DD'), 'Good', 'Ceilings');

INSERT INTO SAFETY_EQUIPMENT (equipment_id, factory_id, equipment_type, quantity, purchase_date, expiry_date, last_inspection, condition_status, location)
VALUES (5, 1, 'Safety Boots', 200, TO_DATE('2025-01-20', 'YYYY-MM-DD'), TO_DATE('2027-01-20', 'YYYY-MM-DD'), TO_DATE('2026-01-10', 'YYYY-MM-DD'), 'Good', 'Store Room');

INSERT INTO SAFETY_EQUIPMENT (equipment_id, factory_id, equipment_type, quantity, purchase_date, expiry_date, last_inspection, condition_status, location)
VALUES (6, 2, 'Emergency Exit Sign (LED)', 30, TO_DATE('2024-03-10', 'YYYY-MM-DD'), TO_DATE('2029-03-10', 'YYYY-MM-DD'), TO_DATE('2026-02-12', 'YYYY-MM-DD'), 'Good', 'Exit Doors');

INSERT INTO GRIEVANCE (grievance_id, worker_id, category, description, submitted_date, status, resolved_date, resolution_notes)
VALUES (1, 1, 'Salary Delay', 'My base salary for last month was delayed by 5 days.', TO_DATE('2026-06-15', 'YYYY-MM-DD'), 'Resolved', TO_DATE('2026-06-20', 'YYYY-MM-DD'), 'Salary was paid on June 20, 2026.');

INSERT INTO GRIEVANCE (grievance_id, worker_id, category, description, submitted_date, status, resolved_date, resolution_notes)
VALUES (2, 4, 'Working Hours', 'Excessive overtime required during the weekend shift.', TO_DATE('2026-06-25', 'YYYY-MM-DD'), 'In Progress', NULL, NULL);

INSERT INTO GRIEVANCE (grievance_id, worker_id, category, description, submitted_date, status, resolved_date, resolution_notes)
VALUES (3, 7, 'Harassment', 'Verbal harassment from line supervisor.', TO_DATE('2026-06-28', 'YYYY-MM-DD'), 'Open', NULL, NULL);

INSERT INTO GRIEVANCE (grievance_id, worker_id, category, description, submitted_date, status, resolved_date, resolution_notes)
VALUES (4, 10, 'Safety', 'No safety masks provided in the dusty fabric cutting section.', TO_DATE('2026-06-30', 'YYYY-MM-DD'), 'Open', NULL, NULL);

INSERT INTO GRIEVANCE (grievance_id, worker_id, category, description, submitted_date, status, resolved_date, resolution_notes)
VALUES (5, 2, 'Facility', 'Clean drinking water is not available on the 3rd floor.', TO_DATE('2026-06-10', 'YYYY-MM-DD'), 'Resolved', TO_DATE('2026-06-14', 'YYYY-MM-DD'), 'Water filter on 3rd floor was serviced and replaced.');

INSERT INTO GRIEVANCE_AUDIT_LOG (log_id, grievance_id, old_status, new_status, changed_by, changed_at)
VALUES (1, 1, 'Open', 'In Progress', 'compliance_officer_1', TO_DATE('2026-06-16', 'YYYY-MM-DD'));

INSERT INTO GRIEVANCE_AUDIT_LOG (log_id, grievance_id, old_status, new_status, changed_by, changed_at)
VALUES (2, 1, 'In Progress', 'Resolved', 'compliance_officer_1', TO_DATE('2026-06-20', 'YYYY-MM-DD'));

INSERT INTO GRIEVANCE_AUDIT_LOG (log_id, grievance_id, old_status, new_status, changed_by, changed_at)
VALUES (3, 2, 'Open', 'In Progress', 'compliance_officer_1', TO_DATE('2026-06-26', 'YYYY-MM-DD'));

INSERT INTO GRIEVANCE_AUDIT_LOG (log_id, grievance_id, old_status, new_status, changed_by, changed_at)
VALUES (4, 5, 'Open', 'Resolved', 'compliance_officer_1', TO_DATE('2026-06-14', 'YYYY-MM-DD'));

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (1, 1, 6, 2026, 12500.00, 10.00, 1000.00, 200.00, 13300.00, 'Paid');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (2, 2, 6, 2026, 14000.00, 15.50, 1800.00, 300.00, 15500.00, 'Paid');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (3, 3, 6, 2026, 10000.00, 5.00, 450.00, 100.00, 10350.00, 'Paid');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (4, 4, 6, 2026, 13000.00, 12.00, 1100.00, 150.00, 13950.00, 'Paid');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (5, 5, 6, 2026, 16000.00, 20.00, 2400.00, 500.00, 17900.00, 'Paid');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (6, 6, 6, 2026, 9800.00, 8.00, 680.00, 0.00, 10480.00, 'Paid');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (7, 7, 6, 2026, 12800.00, 11.50, 1150.00, 250.00, 13700.00, 'Pending');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (8, 8, 6, 2026, 11500.00, 18.00, 1700.00, 100.00, 13100.00, 'Pending');

INSERT INTO SAFETY_ALERT (alert_id, factory_id, equipment_id, alert_type, alert_date, is_acknowledged)
VALUES (1, 1, 1, 'Equipment Expiry', TO_DATE('2026-07-01', 'YYYY-MM-DD'), 'N');

INSERT INTO SAFETY_ALERT (alert_id, factory_id, equipment_id, alert_type, alert_date, is_acknowledged)
VALUES (2, 3, 3, 'Equipment Expiry', TO_DATE('2026-07-02', 'YYYY-MM-DD'), 'N');

INSERT INTO ERROR_LOG (log_id, proc_name, error_code, error_message, logged_at)
VALUES (1, 'PROCESS_SALARY', -20001, 'Worker not active', TO_DATE('2026-06-30', 'YYYY-MM-DD'));

COMMIT;
