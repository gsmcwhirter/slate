CREATE TABLE IF NOT EXISTS consultant_tags(
	ct_id TINYINT unsigned not null auto_increment,
	PRIMARY KEY(ct_id),
	ct_label tinytext not null,
	ct_color tinytext not null
) ENGINE=InnoDB;
