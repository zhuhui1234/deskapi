CREATE TABLE idatadb.idt_logs
		(
		log_id INT PRIMARY KEY AUTO_INCREMENT,
		log_user VARCHAR(100) /*用户*/,
		log_companyID VARCHAR(50) /*公司ID*/,
		log_type VARCHAR(100) /*日志类型*/,
		log_resource VARCHAR(100) /*来源*/,
		log_status VARCHAR(50) /*状态*/,
		log_subid VARCHAR(50) /*子ID*/,
		log_action VARCHAR(50) /*动作*/,
		log_ip VARCHAR(50) /*访问ip*/,
		log_content VARCHAR(200) /*日志备注*/,
		log_fingerprint VARCHAR(200) /*日志指纹*/,
		log_datetime TIMESTAMP
		);

CREATE UNIQUE INDEX idt_logs_log_id_uindex ON idatadb.idt_logs (log_id);

ALTER TABLE idatadb.idt_logs COMMENT = 'idata logs';

ALTER TABLE idatadb.idt_logs ADD log_level INT/*日志级别*/ NULL;