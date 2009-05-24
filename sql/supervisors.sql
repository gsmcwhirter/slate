CREATE TABLE IF NOT EXISTS supervisors(
	s_id BIGINT unsigned not null auto_increment,
	PRIMARY KEY(s_id),
	u_id BIGINT unsigned not null,
	UNIQUE KEY(u_id)
) ENGINE=InnoDB;
