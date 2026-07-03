DROP TABLE SAFETY_ALERT;
DROP TABLE ERROR_LOG;
DROP TABLE SALARY_RECORD;
DROP TABLE GRIEVANCE_AUDIT_LOG;
DROP TABLE GRIEVANCE;
DROP TABLE SAFETY_EQUIPMENT;
DROP TABLE CERTIFICATION;
DROP TABLE AUDIT_RECORD;
DROP TABLE WORKER;
DROP TABLE USER_;
DROP TABLE BUYER_FACTORY;
DROP TABLE BUYER;
DROP TABLE FACTORY;

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
  factory_id NUMBER REFERENCES FACTORY(factory_id) ON DELETE SET NULL,
  email VARCHAR2(100) NOT NULL UNIQUE,
  status VARCHAR2(20) DEFAULT 'Active',
  CONSTRAINT chk_user_role CHECK (role IN ('admin','compliance_officer','inspector','buyer_user','worker'))
);

CREATE TABLE AUDIT_RECORD (
  audit_id NUMBER PRIMARY KEY,
  factory_id NUMBER NOT NULL REFERENCES FACTORY(factory_id),
  inspector_id NUMBER REFERENCES USER_(user_id) ON DELETE SET NULL,
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
  equipment_id NUMBER REFERENCES SAFETY_EQUIPMENT(equipment_id) ON DELETE SET NULL,
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

INSERT INTO FACTORY (factory_id, factory_name, registration_no, address, district, division, total_workers, compliance_status, compliance_score, last_audit_date, next_audit_date, contact_person, phone, email)
VALUES (5, 'Narayanganj Knittings Ltd', 'REG-005', 'Chashara, Narayanganj', 'Narayanganj', 'Dhaka', 3, 'Compliant', 90.00, TO_DATE('2026-02-15', 'YYYY-MM-DD'), TO_DATE('2026-08-15', 'YYYY-MM-DD'), 'Tanvir Rahman', '01755555555', 'narayanganj@factory.com');

INSERT INTO FACTORY (factory_id, factory_name, registration_no, address, district, division, total_workers, compliance_status, compliance_score, last_audit_date, next_audit_date, contact_person, phone, email)
VALUES (6, 'Khulna Denim Corp', 'REG-006', 'Khalishpur, Khulna', 'Khulna', 'Khulna', 3, 'At Risk', 55.00, TO_DATE('2026-03-20', 'YYYY-MM-DD'), TO_DATE('2026-09-20', 'YYYY-MM-DD'), 'Mostafa Kamal', '01766666666', 'khulna@factory.com');

INSERT INTO FACTORY (factory_id, factory_name, registration_no, address, district, division, total_workers, compliance_status, compliance_score, last_audit_date, next_audit_date, contact_person, phone, email)
VALUES (7, 'Rajshahi Silk Weavers', 'REG-007', 'BSCIC Industrial Area, Rajshahi', 'Rajshahi', 'Rajshahi', 3, 'Compliant', 82.00, TO_DATE('2026-04-05', 'YYYY-MM-DD'), TO_DATE('2026-10-05', 'YYYY-MM-DD'), 'Abdur Rahim', '01777777777', 'rajshahi@factory.com');

INSERT INTO FACTORY (factory_id, factory_name, registration_no, address, district, division, total_workers, compliance_status, compliance_score, last_audit_date, next_audit_date, contact_person, phone, email)
VALUES (8, 'Barisal Apparel Ltd', 'REG-008', 'Rupatali, Barisal', 'Barisal', 'Barisal', 3, 'Pending', 0.00, NULL, TO_DATE('2026-08-01', 'YYYY-MM-DD'), 'Siddiqur Rahman', '01788888888', 'barisal@factory.com');

INSERT INTO FACTORY (factory_id, factory_name, registration_no, address, district, division, total_workers, compliance_status, compliance_score, last_audit_date, next_audit_date, contact_person, phone, email)
VALUES (9, 'Rangpur Fashion Source', 'REG-009', 'Tajhat, Rangpur', 'Rangpur', 'Rangpur', 3, 'Review Needed', 68.00, TO_DATE('2026-01-10', 'YYYY-MM-DD'), TO_DATE('2026-07-10', 'YYYY-MM-DD'), 'Mizanur Rahman', '01799999999', 'rangpur@factory.com');

INSERT INTO FACTORY (factory_id, factory_name, registration_no, address, district, division, total_workers, compliance_status, compliance_score, last_audit_date, next_audit_date, contact_person, phone, email)
VALUES (10, 'Mymensingh Garments', 'REG-010', 'Valuka, Mymensingh', 'Mymensingh', 'Mymensingh', 3, 'Compliant', 78.50, TO_DATE('2026-05-18', 'YYYY-MM-DD'), TO_DATE('2026-11-18', 'YYYY-MM-DD'), 'Jamil Hossain', '01700000000', 'mymensingh@factory.com');

INSERT INTO USER_ (user_id, username, password_hash, role, full_name, factory_id, email, status)
VALUES (1, 'admin_user', '$2y$10$xfFhvcPZiGZdd0dYG3fAUeFVtwE82/hiD4iQRtVRFhg6lG7x30eYy', 'admin', 'System Administrator', NULL, 'admin@garmentguard.com', 'Active');

INSERT INTO USER_ (user_id, username, password_hash, role, full_name, factory_id, email, status)
VALUES (2, 'compliance_officer_1', '$2y$10$xfFhvcPZiGZdd0dYG3fAUeFVtwE82/hiD4iQRtVRFhg6lG7x30eYy', 'compliance_officer', 'Rahman Compliance', 1, 'compliance@dhaka.com', 'Active');

INSERT INTO USER_ (user_id, username, password_hash, role, full_name, factory_id, email, status)
VALUES (3, 'inspector_1', '$2y$10$xfFhvcPZiGZdd0dYG3fAUeFVtwE82/hiD4iQRtVRFhg6lG7x30eYy', 'inspector', 'AUDIT_RECORD Inspector', NULL, 'inspector@garmentguard.com', 'Active');

INSERT INTO USER_ (user_id, username, password_hash, role, full_name, factory_id, email, status)
VALUES (4, 'buyer_user_1', '$2y$10$xfFhvcPZiGZdd0dYG3fAUeFVtwE82/hiD4iQRtVRFhg6lG7x30eYy', 'buyer_user', 'Buyer Representative', NULL, 'buyer@hm.com', 'Active');

INSERT INTO USER_ (user_id, username, password_hash, role, full_name, factory_id, email, status)
VALUES (5, 'worker_user_1', '$2y$10$xfFhvcPZiGZdd0dYG3fAUeFVtwE82/hiD4iQRtVRFhg6lG7x30eYy', 'worker', 'Worker User', 2, 'worker_user@gazipur.com', 'Active');

INSERT INTO USER_ (user_id, username, password_hash, role, full_name, factory_id, email, status)
VALUES (6, 'compliance_officer_2', '$2y$10$xfFhvcPZiGZdd0dYG3fAUeFVtwE82/hiD4iQRtVRFhg6lG7x30eYy', 'compliance_officer', 'Tanvir Compliance', 5, 'compliance@narayanganj.com', 'Active');

INSERT INTO USER_ (user_id, username, password_hash, role, full_name, factory_id, email, status)
VALUES (7, 'compliance_officer_3', '$2y$10$xfFhvcPZiGZdd0dYG3fAUeFVtwE82/hiD4iQRtVRFhg6lG7x30eYy', 'compliance_officer', 'Kamal Compliance', 6, 'compliance@khulna.com', 'Active');

INSERT INTO USER_ (user_id, username, password_hash, role, full_name, factory_id, email, status)
VALUES (8, 'compliance_officer_4', '$2y$10$xfFhvcPZiGZdd0dYG3fAUeFVtwE82/hiD4iQRtVRFhg6lG7x30eYy', 'compliance_officer', 'Rahim Compliance', 7, 'compliance@rajshahi.com', 'Active');

INSERT INTO USER_ (user_id, username, password_hash, role, full_name, factory_id, email, status)
VALUES (9, 'compliance_officer_5', '$2y$10$xfFhvcPZiGZdd0dYG3fAUeFVtwE82/hiD4iQRtVRFhg6lG7x30eYy', 'compliance_officer', 'Siddiq Compliance', 8, 'compliance@barisal.com', 'Active');

INSERT INTO USER_ (user_id, username, password_hash, role, full_name, factory_id, email, status)
VALUES (10, 'compliance_officer_6', '$2y$10$xfFhvcPZiGZdd0dYG3fAUeFVtwE82/hiD4iQRtVRFhg6lG7x30eYy', 'compliance_officer', 'Mizan Compliance', 9, 'compliance@rangpur.com', 'Active');

INSERT INTO USER_ (user_id, username, password_hash, role, full_name, factory_id, email, status)
VALUES (11, 'compliance_officer_7', '$2y$10$xfFhvcPZiGZdd0dYG3fAUeFVtwE82/hiD4iQRtVRFhg6lG7x30eYy', 'compliance_officer', 'Jamil Compliance', 10, 'compliance@mymensingh.com', 'Active');

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

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (13, 5, 'Mina Khatun', 'NID-10013', 'Sewing Operator', TO_DATE('2024-06-10', 'YYYY-MM-DD'), 12500.00, 'Day', 'Active', '01755555121', 'mina@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (14, 5, 'Abdur Rob', 'NID-10014', 'Quality Inspector', TO_DATE('2024-07-12', 'YYYY-MM-DD'), 14500.00, 'Day', 'Active', '01755555122', 'rob@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (15, 5, 'Sujon Mia', 'NID-10015', 'Helper', TO_DATE('2025-01-15', 'YYYY-MM-DD'), 10000.00, 'Night', 'Active', '01755555123', 'sujon@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (16, 6, 'Rokeya Begum', 'NID-10016', 'Sewing Operator', TO_DATE('2024-03-20', 'YYYY-MM-DD'), 13000.00, 'Day', 'Active', '01766666121', 'rokeya@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (17, 6, 'Arif Islam', 'NID-10017', 'Ironman', TO_DATE('2024-05-18', 'YYYY-MM-DD'), 11800.00, 'Day', 'Active', '01766666122', 'arif@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (18, 6, 'Salma Akter', 'NID-10018', 'Helper', TO_DATE('2025-02-01', 'YYYY-MM-DD'), 9900.00, 'Night', 'Active', '01766666123', 'salma@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (19, 7, 'Habib Rahman', 'NID-10019', 'Sewing Operator', TO_DATE('2024-04-05', 'YYYY-MM-DD'), 12800.00, 'Day', 'Active', '01777777121', 'habib@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (20, 7, 'Najma Begum', 'NID-10020', 'Cutting Master', TO_DATE('2024-08-15', 'YYYY-MM-DD'), 16200.00, 'Day', 'Active', '01777777122', 'najma@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (21, 7, 'Liton Mia', 'NID-10021', 'Helper', TO_DATE('2025-03-10', 'YYYY-MM-DD'), 10100.00, 'Night', 'Active', '01777777123', 'liton@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (22, 8, 'Parveen Akter', 'NID-10022', 'Sewing Operator', TO_DATE('2024-05-12', 'YYYY-MM-DD'), 12600.00, 'Day', 'Active', '01788888121', 'parveen@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (23, 8, 'Sohel Rana', 'NID-10023', 'Quality Inspector', TO_DATE('2024-09-01', 'YYYY-MM-DD'), 14200.00, 'Day', 'Active', '01788888122', 'sohel@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (24, 8, 'Rubel Hossain', 'NID-10024', 'Helper', TO_DATE('2025-01-20', 'YYYY-MM-DD'), 9800.00, 'Night', 'Active', '01788888123', 'rubel@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (25, 9, 'Khadiza Begum', 'NID-10025', 'Sewing Operator', TO_DATE('2024-02-15', 'YYYY-MM-DD'), 12900.00, 'Day', 'Active', '01799999121', 'khadiza@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (26, 9, 'Milon Ahmed', 'NID-10026', 'Ironman', TO_DATE('2024-06-25', 'YYYY-MM-DD'), 11700.00, 'Day', 'Active', '01799999122', 'milon@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (27, 9, 'Tania Akter', 'NID-10027', 'Helper', TO_DATE('2025-03-01', 'YYYY-MM-DD'), 10000.00, 'Night', 'Active', '01799999123', 'tania@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (28, 10, 'Harun or Rashid', 'NID-10028', 'Sewing Operator', TO_DATE('2024-07-20', 'YYYY-MM-DD'), 13100.00, 'Day', 'Active', '01700000121', 'harun@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (29, 10, 'Fatema Begum', 'NID-10029', 'Line Chief', TO_DATE('2023-12-10', 'YYYY-MM-DD'), 18500.00, 'Day', 'Active', '01700000122', 'fatema@factory.com');

INSERT INTO WORKER (worker_id, factory_id, full_name, national_id, designation, join_date, base_salary, shift, status, phone, email)
VALUES (30, 10, 'Siddique Mia', 'NID-10030', 'Helper', TO_DATE('2025-02-15', 'YYYY-MM-DD'), 10200.00, 'Night', 'Active', '01700000123', 'siddique@factory.com');

INSERT INTO AUDIT_RECORD (audit_id, factory_id, inspector_id, audit_date, next_scheduled, score, result, findings, recommendations)
VALUES (1, 1, 3, TO_DATE('2026-01-15', 'YYYY-MM-DD'), TO_DATE('2026-07-15', 'YYYY-MM-DD'), 88.00, 'Pass', 'Good working conditions, minor safety issues resolved.', 'Maintain current standards.');

INSERT INTO AUDIT_RECORD (audit_id, factory_id, inspector_id, audit_date, next_scheduled, score, result, findings, recommendations)
VALUES (2, 2, 3, TO_DATE('2026-02-10', 'YYYY-MM-DD'), TO_DATE('2026-08-10', 'YYYY-MM-DD'), 62.00, 'Pass', 'Some exits blocked, fire extinguishers need service.', 'Clear exits and service all safety equipment.');

INSERT INTO AUDIT_RECORD (audit_id, factory_id, inspector_id, audit_date, next_scheduled, score, result, findings, recommendations)
VALUES (3, 3, 3, TO_DATE('2026-03-05', 'YYYY-MM-DD'), TO_DATE('2026-06-05', 'YYYY-MM-DD'), 38.00, 'Fail', 'Multiple safety violations, no fire drill logs, structural concerns.', 'Conduct immediate repairs, perform fire drills, and re-AUDIT_RECORD.');

INSERT INTO AUDIT_RECORD (audit_id, factory_id, inspector_id, audit_date, next_scheduled, score, result, findings, recommendations)
VALUES (4, 4, 3, TO_DATE('2026-04-12', 'YYYY-MM-DD'), TO_DATE('2026-10-12', 'YYYY-MM-DD'), 75.00, 'Pass', 'Satisfactory compliance, minor ventilation issues.', 'Improve ventilation in the cutting area.');

INSERT INTO AUDIT_RECORD (audit_id, factory_id, inspector_id, audit_date, next_scheduled, score, result, findings, recommendations)
VALUES (5, 5, 3, TO_DATE('2026-02-15', 'YYYY-MM-DD'), TO_DATE('2026-08-15', 'YYYY-MM-DD'), 90.00, 'Pass', 'Excellent structural safety, well-maintained facilities.', 'Keep up the good performance.');

INSERT INTO AUDIT_RECORD (audit_id, factory_id, inspector_id, audit_date, next_scheduled, score, result, findings, recommendations)
VALUES (6, 6, 3, TO_DATE('2026-03-20', 'YYYY-MM-DD'), TO_DATE('2026-09-20', 'YYYY-MM-DD'), 55.00, 'Fail', 'Exits partially blocked, structural cracks detected.', 'Remediate building structural cracks immediately.');

INSERT INTO AUDIT_RECORD (audit_id, factory_id, inspector_id, audit_date, next_scheduled, score, result, findings, recommendations)
VALUES (7, 7, 3, TO_DATE('2026-04-05', 'YYYY-MM-DD'), TO_DATE('2026-10-05', 'YYYY-MM-DD'), 82.00, 'Pass', 'Good health standards, sound working hours.', 'Improve lighting in sewing section.');

INSERT INTO AUDIT_RECORD (audit_id, factory_id, inspector_id, audit_date, next_scheduled, score, result, findings, recommendations)
VALUES (8, 8, 3, TO_DATE('2026-05-01', 'YYYY-MM-DD'), TO_DATE('2026-08-01', 'YYYY-MM-DD'), NULL, 'Pending', 'Initial AUDIT_RECORD scheduled but not fully completed due to setup delay.', 'Reschedule and finalize AUDIT_RECORD.');

INSERT INTO AUDIT_RECORD (audit_id, factory_id, inspector_id, audit_date, next_scheduled, score, result, findings, recommendations)
VALUES (9, 9, 3, TO_DATE('2026-01-10', 'YYYY-MM-DD'), TO_DATE('2026-07-10', 'YYYY-MM-DD'), 68.00, 'Pass', 'Electrical wiring exposed in packing area.', 'Secure all electrical wiring and panels.');

INSERT INTO AUDIT_RECORD (audit_id, factory_id, inspector_id, audit_date, next_scheduled, score, result, findings, recommendations)
VALUES (10, 10, 3, TO_DATE('2026-05-18', 'YYYY-MM-DD'), TO_DATE('2026-11-18', 'YYYY-MM-DD'), 78.50, 'Pass', 'Satisfactory overall conditions.', 'Increase first aid training sessions.');

INSERT INTO BUYER (buyer_id, buyer_name, country, contact_name, email, phone, brand_name)
VALUES (1, 'H&M', 'Sweden', 'Anna Larsson', 'anna@hm.com', '+4681234567', 'H&M');

INSERT INTO BUYER (buyer_id, buyer_name, country, contact_name, email, phone, brand_name)
VALUES (2, 'Walmart', 'USA', 'John Smith', 'john.smith@walmart.com', '+1479123456', 'Walmart');

INSERT INTO BUYER (buyer_id, buyer_name, country, contact_name, email, phone, brand_name)
VALUES (3, 'Zara', 'Spain', 'Marta Ortega', 'marta@zara.com', '+34981180000', 'Zara');

INSERT INTO BUYER (buyer_id, buyer_name, country, contact_name, email, phone, brand_name)
VALUES (4, 'Levi Strauss & Co.', 'USA', 'Charles Bergh', 'charles.bergh@levis.com', '+14155016000', 'Levis');

INSERT INTO BUYER (buyer_id, buyer_name, country, contact_name, email, phone, brand_name)
VALUES (5, 'Uniqlo', 'Japan', 'Tadashi Yanai', 'tadashi@uniqlo.com', '+81368650050', 'Uniqlo');

INSERT INTO BUYER_FACTORY (buyer_id, factory_id, since_date, contract_status)
VALUES (1, 1, TO_DATE('2024-01-01', 'YYYY-MM-DD'), 'Active');

INSERT INTO BUYER_FACTORY (buyer_id, factory_id, since_date, contract_status)
VALUES (1, 2, TO_DATE('2024-06-15', 'YYYY-MM-DD'), 'Active');

INSERT INTO BUYER_FACTORY (buyer_id, factory_id, since_date, contract_status)
VALUES (2, 3, TO_DATE('2023-09-10', 'YYYY-MM-DD'), 'Active');

INSERT INTO BUYER_FACTORY (buyer_id, factory_id, since_date, contract_status)
VALUES (2, 4, TO_DATE('2025-02-01', 'YYYY-MM-DD'), 'Active');

INSERT INTO BUYER_FACTORY (buyer_id, factory_id, since_date, contract_status)
VALUES (3, 5, TO_DATE('2024-01-10', 'YYYY-MM-DD'), 'Active');

INSERT INTO BUYER_FACTORY (buyer_id, factory_id, since_date, contract_status)
VALUES (3, 6, TO_DATE('2024-05-15', 'YYYY-MM-DD'), 'Active');

INSERT INTO BUYER_FACTORY (buyer_id, factory_id, since_date, contract_status)
VALUES (4, 7, TO_DATE('2023-11-01', 'YYYY-MM-DD'), 'Active');

INSERT INTO BUYER_FACTORY (buyer_id, factory_id, since_date, contract_status)
VALUES (4, 8, TO_DATE('2025-03-01', 'YYYY-MM-DD'), 'Active');

INSERT INTO BUYER_FACTORY (buyer_id, factory_id, since_date, contract_status)
VALUES (5, 9, TO_DATE('2024-08-20', 'YYYY-MM-DD'), 'Active');

INSERT INTO BUYER_FACTORY (buyer_id, factory_id, since_date, contract_status)
VALUES (5, 10, TO_DATE('2025-01-15', 'YYYY-MM-DD'), 'Active');

INSERT INTO CERTIFICATION (cert_id, factory_id, cert_name, issuing_body, issue_date, expiry_date, status)
VALUES (1, 1, 'OEKO-TEX Standard 100', 'OEKO-TEX Association', TO_DATE('2025-05-10', 'YYYY-MM-DD'), TO_DATE('2027-05-10', 'YYYY-MM-DD'), 'Active');

INSERT INTO CERTIFICATION (cert_id, factory_id, cert_name, issuing_body, issue_date, expiry_date, status)
VALUES (2, 2, 'BSCI Code of Conduct', 'amfori', TO_DATE('2025-08-20', 'YYYY-MM-DD'), TO_DATE('2026-08-20', 'YYYY-MM-DD'), 'Active');

INSERT INTO CERTIFICATION (cert_id, factory_id, cert_name, issuing_body, issue_date, expiry_date, status)
VALUES (3, 3, 'ISO 9001:2015', 'SGS', TO_DATE('2024-02-15', 'YYYY-MM-DD'), TO_DATE('2025-02-15', 'YYYY-MM-DD'), 'Expired');

INSERT INTO CERTIFICATION (cert_id, factory_id, cert_name, issuing_body, issue_date, expiry_date, status)
VALUES (4, 4, 'WRAP Certification', 'WRAP', TO_DATE('2025-11-01', 'YYYY-MM-DD'), TO_DATE('2026-11-01', 'YYYY-MM-DD'), 'Active');

INSERT INTO CERTIFICATION (cert_id, factory_id, cert_name, issuing_body, issue_date, expiry_date, status)
VALUES (5, 5, 'ISO 14001:2015', 'SGS', TO_DATE('2025-06-01', 'YYYY-MM-DD'), TO_DATE('2028-06-01', 'YYYY-MM-DD'), 'Active');

INSERT INTO CERTIFICATION (cert_id, factory_id, cert_name, issuing_body, issue_date, expiry_date, status)
VALUES (6, 6, 'OEKO-TEX Standard 100', 'OEKO-TEX Association', TO_DATE('2025-09-10', 'YYYY-MM-DD'), TO_DATE('2027-09-10', 'YYYY-MM-DD'), 'Active');

INSERT INTO CERTIFICATION (cert_id, factory_id, cert_name, issuing_body, issue_date, expiry_date, status)
VALUES (7, 7, 'BSCI Code of Conduct', 'amfori', TO_DATE('2025-10-15', 'YYYY-MM-DD'), TO_DATE('2026-10-15', 'YYYY-MM-DD'), 'Active');

INSERT INTO CERTIFICATION (cert_id, factory_id, cert_name, issuing_body, issue_date, expiry_date, status)
VALUES (8, 8, 'WRAP Certification', 'WRAP', TO_DATE('2025-12-01', 'YYYY-MM-DD'), TO_DATE('2026-12-01', 'YYYY-MM-DD'), 'Active');

INSERT INTO CERTIFICATION (cert_id, factory_id, cert_name, issuing_body, issue_date, expiry_date, status)
VALUES (9, 9, 'ISO 9001:2015', 'SGS', TO_DATE('2024-03-20', 'YYYY-MM-DD'), TO_DATE('2025-03-20', 'YYYY-MM-DD'), 'Expired');

INSERT INTO CERTIFICATION (cert_id, factory_id, cert_name, issuing_body, issue_date, expiry_date, status)
VALUES (10, 10, 'Sedex/SMETA', 'Sedex', TO_DATE('2026-01-10', 'YYYY-MM-DD'), TO_DATE('2027-01-10', 'YYYY-MM-DD'), 'Active');

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

INSERT INTO SAFETY_EQUIPMENT (equipment_id, factory_id, equipment_type, quantity, purchase_date, expiry_date, last_inspection, condition_status, location)
VALUES (7, 5, 'Fire Extinguisher CO2', 40, TO_DATE('2024-05-10', 'YYYY-MM-DD'), TO_DATE('2026-05-10', 'YYYY-MM-DD'), TO_DATE('2026-01-15', 'YYYY-MM-DD'), 'Critical', 'All Floors');

INSERT INTO SAFETY_EQUIPMENT (equipment_id, factory_id, equipment_type, quantity, purchase_date, expiry_date, last_inspection, condition_status, location)
VALUES (8, 6, 'Fire Hose Reel', 8, TO_DATE('2023-10-15', 'YYYY-MM-DD'), TO_DATE('2028-10-15', 'YYYY-MM-DD'), TO_DATE('2026-02-20', 'YYYY-MM-DD'), 'Good', 'Ground Floor');

INSERT INTO SAFETY_EQUIPMENT (equipment_id, factory_id, equipment_type, quantity, purchase_date, expiry_date, last_inspection, condition_status, location)
VALUES (9, 7, 'First Aid Kit', 20, TO_DATE('2025-06-01', 'YYYY-MM-DD'), TO_DATE('2026-06-01', 'YYYY-MM-DD'), TO_DATE('2026-03-10', 'YYYY-MM-DD'), 'Fair', 'Production Area');

INSERT INTO SAFETY_EQUIPMENT (equipment_id, factory_id, equipment_type, quantity, purchase_date, expiry_date, last_inspection, condition_status, location)
VALUES (10, 8, 'Smoke Detector', 80, TO_DATE('2024-12-05', 'YYYY-MM-DD'), TO_DATE('2029-12-05', 'YYYY-MM-DD'), TO_DATE('2026-04-15', 'YYYY-MM-DD'), 'Good', 'Ceilings');

INSERT INTO SAFETY_EQUIPMENT (equipment_id, factory_id, equipment_type, quantity, purchase_date, expiry_date, last_inspection, condition_status, location)
VALUES (11, 9, 'Emergency Exit Sign (LED)', 25, TO_DATE('2024-04-10', 'YYYY-MM-DD'), TO_DATE('2029-04-10', 'YYYY-MM-DD'), TO_DATE('2026-02-18', 'YYYY-MM-DD'), 'Good', 'Exit Doors');

INSERT INTO SAFETY_EQUIPMENT (equipment_id, factory_id, equipment_type, quantity, purchase_date, expiry_date, last_inspection, condition_status, location)
VALUES (12, 10, 'Safety Boots', 150, TO_DATE('2025-03-01', 'YYYY-MM-DD'), TO_DATE('2027-03-01', 'YYYY-MM-DD'), TO_DATE('2026-01-20', 'YYYY-MM-DD'), 'Good', 'Store Room');

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

INSERT INTO GRIEVANCE (grievance_id, worker_id, category, description, submitted_date, status, resolved_date, resolution_notes)
VALUES (6, 13, 'Facility', 'Insufficient cooling fans in sewing section.', TO_DATE('2026-06-20', 'YYYY-MM-DD'), 'Resolved', TO_DATE('2026-06-24', 'YYYY-MM-DD'), 'Added 2 new wall fans near worker station.');

INSERT INTO GRIEVANCE (grievance_id, worker_id, category, description, submitted_date, status, resolved_date, resolution_notes)
VALUES (7, 16, 'Salary Delay', 'Salary payment delayed by 3 days.', TO_DATE('2026-06-22', 'YYYY-MM-DD'), 'Open', NULL, NULL);

INSERT INTO GRIEVANCE (grievance_id, worker_id, category, description, submitted_date, status, resolved_date, resolution_notes)
VALUES (8, 19, 'Working Hours', 'Forced overtime on holidays.', TO_DATE('2026-06-25', 'YYYY-MM-DD'), 'In Progress', NULL, NULL);

INSERT INTO GRIEVANCE_AUDIT_LOG (log_id, grievance_id, old_status, new_status, changed_by, changed_at)
VALUES (1, 1, 'Open', 'In Progress', 'compliance_officer_1', TO_DATE('2026-06-16', 'YYYY-MM-DD'));

INSERT INTO GRIEVANCE_AUDIT_LOG (log_id, grievance_id, old_status, new_status, changed_by, changed_at)
VALUES (2, 1, 'In Progress', 'Resolved', 'compliance_officer_1', TO_DATE('2026-06-20', 'YYYY-MM-DD'));

INSERT INTO GRIEVANCE_AUDIT_LOG (log_id, grievance_id, old_status, new_status, changed_by, changed_at)
VALUES (3, 2, 'Open', 'In Progress', 'compliance_officer_1', TO_DATE('2026-06-26', 'YYYY-MM-DD'));

INSERT INTO GRIEVANCE_AUDIT_LOG (log_id, grievance_id, old_status, new_status, changed_by, changed_at)
VALUES (4, 5, 'Open', 'Resolved', 'compliance_officer_1', TO_DATE('2026-06-14', 'YYYY-MM-DD'));

INSERT INTO GRIEVANCE_AUDIT_LOG (log_id, grievance_id, old_status, new_status, changed_by, changed_at)
VALUES (5, 6, 'Open', 'Resolved', 'compliance_officer_2', TO_DATE('2026-06-24', 'YYYY-MM-DD'));

INSERT INTO GRIEVANCE_AUDIT_LOG (log_id, grievance_id, old_status, new_status, changed_by, changed_at)
VALUES (6, 8, 'Open', 'In Progress', 'compliance_officer_4', TO_DATE('2026-06-26', 'YYYY-MM-DD'));

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

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (9, 13, 6, 2026, 12500.00, 5.00, 500.00, 100.00, 12900.00, 'Paid');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (10, 14, 6, 2026, 14500.00, 10.00, 1200.00, 200.00, 15500.00, 'Paid');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (11, 15, 6, 2026, 10000.00, 0.00, 0.00, 0.00, 10000.00, 'Paid');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (12, 16, 6, 2026, 13000.00, 8.00, 850.00, 150.00, 13700.00, 'Paid');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (13, 17, 6, 2026, 11800.00, 12.00, 1100.00, 100.00, 12800.00, 'Paid');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (14, 18, 6, 2026, 9900.00, 4.00, 350.00, 50.00, 10200.00, 'Paid');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (15, 19, 6, 2026, 12800.00, 15.50, 1500.00, 200.00, 14100.00, 'Pending');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (16, 20, 6, 2026, 16200.00, 20.00, 2500.00, 400.00, 18300.00, 'Pending');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (17, 21, 6, 2026, 10100.00, 6.00, 550.00, 100.00, 10550.00, 'Pending');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (18, 22, 6, 2026, 12600.00, 7.00, 700.00, 150.00, 13150.00, 'Paid');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (19, 23, 6, 2026, 14200.00, 14.00, 1500.00, 300.00, 15400.00, 'Paid');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (20, 24, 6, 2026, 9800.00, 5.00, 450.00, 0.00, 10250.00, 'Paid');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (21, 25, 6, 2026, 12900.00, 9.00, 900.00, 200.00, 13600.00, 'Pending');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (22, 26, 6, 2026, 11700.00, 11.00, 1100.00, 100.00, 12700.00, 'Pending');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (23, 27, 6, 2026, 10000.00, 3.00, 300.00, 50.00, 10250.00, 'Pending');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (24, 28, 6, 2026, 13100.00, 10.00, 1100.00, 150.00, 14050.00, 'Paid');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (25, 29, 6, 2026, 18500.00, 22.00, 2800.00, 500.00, 20800.00, 'Paid');

INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
VALUES (26, 30, 6, 2026, 10200.00, 8.00, 750.00, 100.00, 10850.00, 'Paid');

INSERT INTO SAFETY_ALERT (alert_id, factory_id, equipment_id, alert_type, alert_date, is_acknowledged)
VALUES (1, 1, 1, 'Equipment Expiry', TO_DATE('2026-07-01', 'YYYY-MM-DD'), 'N');

INSERT INTO SAFETY_ALERT (alert_id, factory_id, equipment_id, alert_type, alert_date, is_acknowledged)
VALUES (2, 3, 3, 'Equipment Expiry', TO_DATE('2026-07-02', 'YYYY-MM-DD'), 'N');

INSERT INTO SAFETY_ALERT (alert_id, factory_id, equipment_id, alert_type, alert_date, is_acknowledged)
VALUES (3, 5, 7, 'Equipment Malfunction', TO_DATE('2026-07-02', 'YYYY-MM-DD'), 'N');

INSERT INTO SAFETY_ALERT (alert_id, factory_id, equipment_id, alert_type, alert_date, is_acknowledged)
VALUES (4, 6, 8, 'Equipment Expiry', TO_DATE('2026-07-03', 'YYYY-MM-DD'), 'N');

INSERT INTO SAFETY_ALERT (alert_id, factory_id, equipment_id, alert_type, alert_date, is_acknowledged)
VALUES (5, 7, 9, 'Equipment Expiry', TO_DATE('2026-07-03', 'YYYY-MM-DD'), 'N');

INSERT INTO ERROR_LOG (log_id, proc_name, error_code, error_message, logged_at)
VALUES (1, 'PROCESS_SALARY', -20001, 'Worker not active', TO_DATE('2026-06-30', 'YYYY-MM-DD'));

COMMIT;

