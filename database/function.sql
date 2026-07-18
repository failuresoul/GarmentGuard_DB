CREATE OR REPLACE FUNCTION fn_compliance_score(p_factory_id IN NUMBER)
RETURN NUMBER AS
  TYPE t_scores IS TABLE OF NUMBER INDEX BY PLS_INTEGER;
  v_scores t_scores;
  v_idx PLS_INTEGER := 1;
  v_score NUMBER := 0;
  
  CURSOR c_audits IS 
    SELECT score FROM AUDIT_RECORD 
    WHERE factory_id = p_factory_id AND score IS NOT NULL 
    ORDER BY audit_date DESC;
BEGIN
  FOR r_audit IN c_audits LOOP
    v_scores(v_idx) := r_audit.score;
    v_idx := v_idx + 1;
    EXIT WHEN v_idx > 3;
  END LOOP;
  
  IF v_scores.COUNT = 0 THEN 
    RETURN 0; 
  ELSIF v_scores.COUNT = 1 THEN 
    v_score := v_scores(1);
  ELSIF v_scores.COUNT = 2 THEN 
    v_score := (v_scores(1) * 0.6) + (v_scores(2) * 0.4);
  ELSE 
    v_score := (v_scores(1) * 0.5) + (v_scores(2) * 0.3) + (v_scores(3) * 0.2);
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
  TYPE t_equipment_list IS VARRAY(100) OF VARCHAR2(100);
  v_equipments t_equipment_list := t_equipment_list();
  v_result VARCHAR2(500) := '';
BEGIN
  FOR r IN (SELECT equipment_type FROM SAFETY_EQUIPMENT
            WHERE factory_id = p_factory_id AND expiry_date BETWEEN SYSDATE AND SYSDATE + 30) LOOP
    v_equipments.EXTEND;
    v_equipments(v_equipments.LAST) := r.equipment_type;
  END LOOP;
  
  IF v_equipments.COUNT = 0 THEN 
    RETURN 'ALL OK'; 
  END IF;
  
  FOR i IN 1..v_equipments.COUNT LOOP
    v_result := v_result || v_equipments(i) || ',';
  END LOOP;
  
  RETURN RTRIM(v_result, ',');
EXCEPTION
  WHEN OTHERS THEN RETURN 'ERROR';
END;
/
