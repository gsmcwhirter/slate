CREATE TABLE IF NOT EXISTS location_zones(
	lz_id TINYINT unsigned not null auto_increment,
	PRIMARY KEY(lz_id),
	lz_name tinytext not null,
	lz_coords tinytext not null
) ENGINE=InnoDB;
