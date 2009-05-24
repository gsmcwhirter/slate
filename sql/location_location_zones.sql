CREATE TABLE IF NOT EXISTS location_location_zones(
	l_id MEDIUMINT unsigned not null,
	KEY(l_id),
	lz_id TINYINT unsigned not null,
	KEY(lz_id)
) ENGINE=InnoDB;
