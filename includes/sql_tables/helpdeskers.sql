CREATE TABLE IF NOT EXISTS helpdeskers(
  id MEDIUMINT unsigned not null auto_increment,
  PRIMARY KEY(id),
  username VARCHAR(20) not null UNIQUE,
  password VARCHAR(255) not null,
  realname TINYTEXT not null,
  KEY(username, password),
  acdaccount ENUM('yes','no') not null default 'no',
  KEY(acdaccount),
  force_pass_change INT(1) not null default '1'
);