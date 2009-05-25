CREATE TABLE IF NOT EXISTS team_leaders(
	t_id INT unsigned not null,
	KEY(t_id),
	u_id BIGINT unsigned not null,
	KEY(u_id)
) ENGINE=InnoDB;
