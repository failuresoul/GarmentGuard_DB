CREATE OR REPLACE FUNCTION fn_compliance_score(p_factory_id IN NUMBER)
RETURN NUMBER AS
  v_score NUMBER := 0;
  v_count NUMBER := 0;
  v_s1 NUMBER := 0;
  v_s2 NUMBER := 0;
  v_s3 NUMBER := 0;
BEGIN
  SELECT COUNT(*) INTO v_count FROM AUDIT_RECORD WHERE factory_id = p_factory_id AND score IS NOT NULL;
  IF v_count = 0 THEN RETURN 0; END IF;
  IF v_count >= 1 THEN
    SELECT score INTO v_s1 FROM (
      SELECT score, ROW_NUMBER() OVER (ORDER BY audit_date DESC) as rn
      FROM AUDIT_RECORD WHERE factory_id = p_factory_id AND score IS NOT NULL
    ) WHERE rn = 1;
  END IF;
  IF v_count >= 2 THEN
    SELECT score INTO v_s2 FROM (
      SELECT score, ROW_NUMBER() OVER (ORDER BY audit_date DESC) as rn
      FROM AUDIT_RECORD WHERE factory_id = p_factory_id AND score IS NOT NULL
    ) WHERE rn = 2;
  END IF;
  IF v_count >= 3 THEN
    SELECT score INTO v_s3 FROM (
      SELECT score, ROW_NUMBER() OVER (ORDER BY audit_date DESC) as rn
      FROM AUDIT_RECORD WHERE factory_id = p_factory_id AND score IS NOT NULL
    ) WHERE rn = 3;
  END IF;
  IF v_count = 1 THEN v_score := v_s1;
  ELSIF v_count = 2 THEN v_score := (v_s1 * 0.6) + (v_s2 * 0.4);
  ELSE v_score := (v_s1 * 0.5) + (v_s2 * 0.3) + (v_s3 * 0.2);
  END IF;
  RETURN ROUND(v_score, 2);
EXCEPTION
  WHEN OTHERS THEN RETURN 0;
END;
/

CREATE OR REPLACE FUNCTION fn_worker_ytd_salary(p_worker_id IN NUMBER, p_year IN NUMBER)
RETURN NUMBER AS
  v_total NUMBER := 0;
BEGIN
  SELECT NVL(SUM(net_salary), 0) INTO v_total
  FROM SALARY_RECORD WHERE worker_id = p_worker_id AND year = p_year;
  RETURN v_total;
EXCEPTION
  WHEN OTHERS THEN RETURN 0;
END;
/

CREATE OR REPLACE FUNCTION fn_is_cert_valid(p_factory_id IN NUMBER, p_cert_name IN VARCHAR2)
RETURN CHAR AS
  v_count NUMBER := 0;
BEGIN
  SELECT COUNT(*) INTO v_count FROM CERTIFICATION
  WHERE factory_id = p_factory_id AND cert_name = p_cert_name
  AND status = 'Active' AND expiry_date >= SYSDATE;
  IF v_count > 0 THEN RETURN 'Y'; ELSE RETURN 'N'; END IF;
EXCEPTION
  WHEN OTHERS THEN RETURN 'N';
END;
/

CREATE OR REPLACE FUNCTION fn_grievance_days(p_grievance_id IN NUMBER)
RETURN NUMBER AS
  v_submitted DATE;
  v_resolved DATE;
BEGIN
  SELECT submitted_date, resolved_date INTO v_submitted, v_resolved
  FROM GRIEVANCE WHERE grievance_id = p_grievance_id;
  IF v_resolved IS NULL THEN
    RETURN ROUND(SYSDATE - v_submitted);
  ELSE
    RETURN ROUND(v_resolved - v_submitted);
  END IF;
EXCEPTION
  WHEN OTHERS THEN RETURN NULL;
END;
/

CREATE OR REPLACE FUNCTION fn_equipment_alert(p_factory_id IN NUMBER)
RETURN VARCHAR2 AS
  v_result VARCHAR2(500) := '';
  v_count NUMBER := 0;
BEGIN
  SELECT COUNT(*) INTO v_count FROM SAFETY_EQUIPMENT
  WHERE factory_id = p_factory_id AND expiry_date BETWEEN SYSDATE AND SYSDATE + 30;
  IF v_count = 0 THEN RETURN 'ALL OK'; END IF;
  FOR r IN (SELECT equipment_type FROM SAFETY_EQUIPMENT
            WHERE factory_id = p_factory_id AND expiry_date BETWEEN SYSDATE AND SYSDATE + 30) LOOP
    v_result := v_result || r.equipment_type || ',';
  END LOOP;
  RETURN RTRIM(v_result, ',');
EXCEPTION
  WHEN OTHERS THEN RETURN 'ERROR';
END;
/
