CREATE TABLE IF NOT EXISTS roles(
	r_id INT unsigned not null auto_increment,
	PRIMARY KEY(r_id),
	r_name tinytext not null
) ENGINE=InnoDB;
