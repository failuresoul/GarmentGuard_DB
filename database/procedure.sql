CREATE OR REPLACE PROCEDURE sp_register_factory(
  p_factory_id IN NUMBER,
  p_name IN VARCHAR2,
  p_reg_no IN VARCHAR2,
  p_address IN VARCHAR2,
  p_district IN VARCHAR2,
  p_division IN VARCHAR2,
  p_workers IN NUMBER,
  p_contact IN VARCHAR2,
  p_phone IN VARCHAR2,
  p_email IN VARCHAR2
) AS
BEGIN
  INSERT INTO FACTORY(factory_id, factory_name, registration_no, address, district, division,
    total_workers, compliance_status, compliance_score, contact_person, phone, email)
  VALUES(p_factory_id, p_name, p_reg_no, p_address, p_district, p_division,
    p_workers, 'Pending', 0, p_contact, p_phone, p_email);
  COMMIT;
EXCEPTION
  WHEN OTHERS THEN
    ROLLBACK;
    RAISE;
END;
/

CREATE OR REPLACE PROCEDURE sp_hire_worker(
  p_worker_id IN NUMBER,
  p_factory_id IN NUMBER,
  p_full_name IN VARCHAR2,
  p_national_id IN VARCHAR2,
  p_designation IN VARCHAR2,
  p_join_date IN DATE,
  p_base_salary IN NUMBER,
  p_shift IN VARCHAR2,
  p_phone IN VARCHAR2,
  p_email IN VARCHAR2
) AS
  v_status VARCHAR2(20);
BEGIN
  SELECT compliance_status INTO v_status FROM FACTORY WHERE factory_id = p_factory_id;
  IF v_status = 'Non-Compliant' THEN
    RAISE_APPLICATION_ERROR(-20001, 'Cannot hire worker. Factory is Non-Compliant.');
  END IF;
  INSERT INTO WORKER(worker_id, factory_id, full_name, national_id, designation,
    join_date, base_salary, shift, status, phone, email)
  VALUES(p_worker_id, p_factory_id, p_full_name, p_national_id, p_designation,
    p_join_date, p_base_salary, p_shift, 'Active', p_phone, p_email);
  COMMIT;
EXCEPTION
  WHEN OTHERS THEN
    ROLLBACK;
    RAISE;
END;
/

CREATE OR REPLACE PROCEDURE sp_submit_grievance(
  p_grievance_id IN NUMBER,
  p_worker_id IN NUMBER,
  p_category IN VARCHAR2,
  p_description IN VARCHAR2
) AS
BEGIN
  INSERT INTO GRIEVANCE(grievance_id, worker_id, category, description, submitted_date, status)
  VALUES(p_grievance_id, p_worker_id, p_category, p_description, SYSDATE, 'Open');
  COMMIT;
EXCEPTION
  WHEN OTHERS THEN
    ROLLBACK;
    RAISE;
END;
/

CREATE OR REPLACE PROCEDURE sp_process_salary(
  p_record_id IN NUMBER,
  p_worker_id IN NUMBER,
  p_month IN NUMBER,
  p_year IN NUMBER,
  p_overtime_hours IN NUMBER,
  p_deductions IN NUMBER
) AS
  v_base NUMBER;
  v_ot_paid NUMBER;
  v_net NUMBER;
  v_count NUMBER;
BEGIN
  SELECT COUNT(*) INTO v_count FROM SALARY_RECORD
  WHERE worker_id = p_worker_id AND month = p_month AND year = p_year;
  IF v_count > 0 THEN
    RAISE_APPLICATION_ERROR(-20003, 'Salary already processed for this month.');
  END IF;
  IF p_overtime_hours > 60 THEN
    RAISE_APPLICATION_ERROR(-20002, 'Overtime cannot exceed 60 hours per month.');
  END IF;
  SELECT base_salary INTO v_base FROM WORKER WHERE worker_id = p_worker_id;
  v_ot_paid := (v_base / 26 / 8) * 1.25 * p_overtime_hours;
  v_net := v_base + v_ot_paid - p_deductions;
  INSERT INTO SALARY_RECORD(record_id, worker_id, month, year, base_amount,
    overtime_hours, overtime_paid, deductions, net_salary, payment_status)
  VALUES(p_record_id, p_worker_id, p_month, p_year, v_base,
    p_overtime_hours, v_ot_paid, p_deductions, v_net, 'Pending');
  COMMIT;
EXCEPTION
  WHEN OTHERS THEN
    ROLLBACK;
    RAISE;
END;
/

CREATE OR REPLACE PROCEDURE sp_schedule_audit(
  p_audit_id IN NUMBER,
  p_factory_id IN NUMBER,
  p_inspector_id IN NUMBER,
  p_audit_date IN DATE,
  p_next_scheduled IN DATE
) AS
  v_count NUMBER;
BEGIN
  SELECT COUNT(*) INTO v_count FROM AUDIT_RECORD
  WHERE factory_id = p_factory_id
  AND EXTRACT(MONTH FROM audit_date) = EXTRACT(MONTH FROM p_audit_date)
  AND EXTRACT(YEAR FROM audit_date) = EXTRACT(YEAR FROM p_audit_date);
  IF v_count > 0 THEN
    RAISE_APPLICATION_ERROR(-20004, 'Audit already scheduled for this factory this month.');
  END IF;
  INSERT INTO AUDIT_RECORD(audit_id, factory_id, inspector_id, audit_date, next_scheduled, result)
  VALUES(p_audit_id, p_factory_id, p_inspector_id, p_audit_date, p_next_scheduled, 'Pending');
  COMMIT;
EXCEPTION
  WHEN OTHERS THEN
    ROLLBACK;
    RAISE;
END;
/

CREATE OR REPLACE PROCEDURE sp_record_audit_score(
  p_audit_id IN NUMBER,
  p_score IN NUMBER,
  p_result IN VARCHAR2,
  p_findings IN VARCHAR2,
  p_recommendations IN VARCHAR2
) AS
  v_factory_id NUMBER;
  v_status VARCHAR2(20);
BEGIN
  SELECT factory_id INTO v_factory_id FROM AUDIT_RECORD WHERE audit_id = p_audit_id;
  IF p_score >= 75 THEN
    v_status := 'Compliant';
  ELSIF p_score >= 40 THEN
    v_status := 'At Risk';
  ELSE
    v_status := 'Non-Compliant';
  END IF;
  UPDATE AUDIT_RECORD SET score = p_score, result = p_result,
    findings = p_findings, recommendations = p_recommendations
  WHERE audit_id = p_audit_id;
  UPDATE FACTORY SET compliance_score = p_score, compliance_status = v_status,
    last_audit_date = SYSDATE, next_audit_date = ADD_MONTHS(SYSDATE, 6)
  WHERE factory_id = v_factory_id;
  COMMIT;
EXCEPTION
  WHEN OTHERS THEN
    ROLLBACK;
    RAISE;
END;
/
