CREATE TABLE IF NOT EXISTS location_appointment_types(
	l_id MEDIUMINT unsigned not null,
	KEY(l_id),
	at_id TINYINT unsigned not null,
	KEY(at_id)
) ENGINE=InnoDB;
