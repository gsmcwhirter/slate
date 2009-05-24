CREATE TABLE IF NOT EXISTS helpdesk_users(
  hd_id BIGINT unsigned not null auto_increment,
  PRIMARY KEY(hd_id),
  u_id BIGINT unsigned not null,
  UNIQUE KEY(u_id),
  hd_acdaccount ENUM('yes','no') not null default 'no',
  KEY(hd_acdaccount),
) ENGINE=InnoDB;
