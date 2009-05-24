CREATE TABLE IF NOT EXISTS users(
  u_id BIGINT unsigned not null auto_increment,
  PRIMARY KEY(u_id),
  u_username VARCHAR(10) not null UNIQUE,
  u_password VARCHAR(255) not null,
  u_salt VARCHAR(255) not null,
  u_realname VARCHAR(255) not null,
  u_force_pass_change INT(1) not null default '1',
  KEY(u_username, u_password),
  u_status enum('active','inactive','deleted') not null default 'active'
) ENGINE=InnoDB;
