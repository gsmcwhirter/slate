CREATE TABLE IF NOT EXISTS user_reports(
	u_id BIGINT unsigned not null,
	KEY(u_id),
	r_id MEDIUMINT unsigned not null,
	KEY(r_id)
) ENGINE=InnoDB;
