CREATE TABLE IF NOT EXISTS role_group_roles(
	rg_id INT unsigned not null,
	KEY(rg_id),
	r_id INT unsigned not null,
	KEY(r_id)
) ENGINE=InnoDB;
