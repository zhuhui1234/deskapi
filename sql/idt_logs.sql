CREATE TABLE idatadb.idt_logs
(
  log_id          INT PRIMARY KEY AUTO_INCREMENT,
  log_user        VARCHAR(100) /*用户*/,
  log_companyID   VARCHAR(50) /*公司ID*/,
  log_type        VARCHAR(100) /*日志类型*/,
  log_resource    VARCHAR(100) /*来源*/,
  log_status      VARCHAR(50) /*状态*/,
  log_subid       VARCHAR(50) /*子ID*/,
  log_action      VARCHAR(50) /*动作*/,
  log_ip          VARCHAR(50) /*访问ip*/,
  log_content     VARCHAR(200) /*日志备注*/,
  log_fingerprint VARCHAR(200) /*日志指纹*/,
  log_datetime    TIMESTAMP
);

CREATE UNIQUE INDEX idt_logs_log_id_uindex
  ON idatadb.idt_logs (log_id);

ALTER TABLE idatadb.idt_logs
  COMMENT = 'idata logs';

ALTER TABLE idatadb.idt_logs
  ADD log_level INT /*日志级别*/ NULL;


CREATE TABLE idatadb.idt_verify_mail
(
  vm_id  INT PRIMARY KEY AUTO_INCREMENT,
  u_id   CHAR(50)  NOT NULL,
  u_mail CHAR(100) NOT NULL,
  c_time INT       NOT NULL,
  u_time INT       NOT NULL,
  status INT             DEFAULT 0
);
CREATE UNIQUE INDEX idt_verify_mail_vm_id_uindex
  ON idatadb.idt_verify_mail (vm_id);

ALTER TABLE idatadb.idt_user
  ADD u_is_check_mail INT DEFAULT 0 NULL;


CREATE TABLE idt_msgs
(
  msg_id      INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  msg_title   VARCHAR(50),
  msg_content TEXT,
  msg_type    INT(20),
  msg_state   TINYINT                  DEFAULT 0,
  msg_uid     VARCHAR(100),
  msg_cdate   DATETIME                 DEFAULT CURRENT_TIMESTAMP(),
  msg_pdt_id  INT(25)                  DEFAULT 0,
  msg_lang    INT                      DEFAULT 0,
  msg_auth    VARCHAR(50)     NULL,
  msg_udate   TIMESTAMP                DEFAULT current_timestamp()
);

CREATE UNIQUE INDEX idt_msgs_msg_id_uindex
  ON idt_msgs (msg_id);

ALTER TABLE idt_msgs
  COMMENT = 'msg_list';


ALTER TABLE idt_msgs
  ADD msg_lang INT DEFAULT 0 NULL;

ALTER TABLE idt_msgs
  ADD msg_auth VARCHAR(50) NULL;

DROP TABLE `idt_devs`;
DROP TABLE `idt_points`;

CREATE TABLE `idt_devs` (
  `dev_id` int(11) NOT NULL,
  `dev_name` varchar(255) NULL DEFAULT '主部门',
  `dev_ename` varchar(255) NULL DEFAULT 'master',
  `dev_state` int(3) NULL DEFAULT 0,
  `cpy_id` int(11) NOT NULL,
  `auther` varchar(255) NULL,
  `cdate` datetime NULL,
  `udate` timestamp NULL,
  PRIMARY KEY (`dev_id`)
);

CREATE TABLE `idt_points` (
  `point_id` int(11) NOT NULL,
  `cpy_id` int(11) NULL,
  `dev_id` int(11) NULL,
  `u_id` varchar(255) NULL,
  `explain` varchar(255) NULL,
  `cdate` datetime NULL DEFAULT NOW(),
  `state` int(11) NULL,
  `type` int(5) NULL,
  `point_value` int(255) NULL,
  `pdt_id` int(11) NULL,
  `update` timestamp NULL,
  PRIMARY KEY (`point_id`)
);

