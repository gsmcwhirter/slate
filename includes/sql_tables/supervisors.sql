CREATE TABLE IF NOT EXISTS supervisors(
  id TINYINT unsigned not null auto_increment,
  PRIMARY KEY(id),
  username VARCHAR(10) not null UNIQUE,
  password VARCHAR(255) not null,
  KEY(username, password),
  realname VARCHAR(255) not null,
  force_pass_change INT(1) not null default '1'
);
