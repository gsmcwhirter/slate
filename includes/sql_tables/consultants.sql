CREATE TABLE IF NOT EXISTS consultants(
  id MEDIUMINT unsigned not null auto_increment,
  PRIMARY KEY(id),
  username VARCHAR(10) not null UNIQUE,
  password VARCHAR(255) not null,
  realname VARCHAR(255) not null,
  tag_id TINYINT unsigned not null,
  KEY(tag_id),
  gender ENUM('M','F') not null default 'M',
  staff ENUM('TRUE','FALSE') not null default 'FALSE',
  appt_perms CHAR(12) not null default '111111111111',
  force_pass_change INT(1) not null default '1',
  KEY(staff),
  KEY(tag_id),
  KEY(username, password),
  pref_send_text ENUM('yes','no') not null default 'no',
  pref_text_address tinytext not null,
  status enum('active','inactive','deleted') not null default 'active'
);
