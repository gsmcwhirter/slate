CREATE TABLE IF NOT EXISTS role_groups(
	rg_id INT unsigned not null auto_increment,
	PRIMARY KEY(rg_id),
	rg_name tinytext not null
) ENGINE=InnoDB;
