CREATE TABLE IF NOT EXISTS supervisor_teams(
	t_id INT unsigned not null,
	s_id BIGINT unsigned not null,
	PRIMARY KEY(t_id, s_id)
) ENGINE=InnoDB;
