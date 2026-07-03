CREATE OR REPLACE TRIGGER trg_salary_net_calc
BEFORE INSERT OR UPDATE ON SALARY_RECORD
FOR EACH ROW
BEGIN
  :NEW.net_salary := :NEW.base_amount + :NEW.overtime_paid - :NEW.deductions;
END;
/

CREATE OR REPLACE TRIGGER trg_worker_count_sync
AFTER INSERT OR DELETE ON WORKER
FOR EACH ROW
BEGIN
  IF INSERTING THEN
    UPDATE FACTORY SET total_workers = total_workers + 1 WHERE factory_id = :NEW.factory_id;
  END IF;
  IF DELETING THEN
    UPDATE FACTORY SET total_workers = total_workers - 1 WHERE factory_id = :OLD.factory_id;
  END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_grievance_log
BEFORE UPDATE OF status ON GRIEVANCE
FOR EACH ROW
BEGIN
  INSERT INTO GRIEVANCE_AUDIT_LOG(log_id, grievance_id, old_status, new_status, changed_by, changed_at)
  VALUES(seq_grievance_log_id.NEXTVAL,
    :NEW.grievance_id, :OLD.status, :NEW.status,
    SYS_CONTEXT('USERENV','SESSION_USER'), SYSDATE);
  IF :NEW.status = 'Resolved' THEN
    :NEW.resolved_date := SYSDATE;
  END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_cert_expiry_check
BEFORE INSERT ON CERTIFICATION
FOR EACH ROW
BEGIN
  IF :NEW.expiry_date < SYSDATE THEN
    RAISE_APPLICATION_ERROR(-20005, 'Cannot add an already expired certification.');
  END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_audit_score_update
FOR UPDATE OF score ON AUDIT_RECORD
COMPOUND TRIGGER
  TYPE t_factories IS TABLE OF FACTORY.factory_id%TYPE;
  v_factories t_factories := t_factories();

  AFTER EACH ROW IS
  BEGIN
    IF :NEW.score IS NOT NULL THEN
      IF :NEW.factory_id MEMBER OF v_factories THEN
        NULL;
      ELSE
        v_factories.EXTEND;
        v_factories(v_factories.LAST) := :NEW.factory_id;
      END IF;
    END IF;
  END AFTER EACH ROW;

  AFTER STATEMENT IS
  BEGIN
    FOR i IN 1..v_factories.COUNT LOOP
      UPDATE FACTORY SET
        compliance_score = fn_compliance_score(v_factories(i)),
        compliance_status = CASE
          WHEN fn_compliance_score(v_factories(i)) >= 75 THEN 'Compliant'
          WHEN fn_compliance_score(v_factories(i)) >= 40 THEN 'At Risk'
          ELSE 'Non-Compliant' END,
        last_audit_date = SYSDATE
      WHERE factory_id = v_factories(i);
    END LOOP;
  END AFTER STATEMENT;
END;
/
