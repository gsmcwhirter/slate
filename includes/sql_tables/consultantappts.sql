CREATE TABLE IF NOT EXISTS consultantappts(
  consultant_id MEDIUMINT unsigned not null,
  appointment_id BIGINT unsigned not null,
  KEY(consultant_id, appointment_id),
  confirmed ENUM('TRUE','FALSE') not null default 'FALSE',
  confirm_ip TINYTEXT not null,
  confirm_timestamp INT unsigned not null,
  confirm_version TINYINT unsigned not null default '0',
  rapid BIGINT unsigned not null auto_increment,
  PRIMARY KEY(rapid)
);
