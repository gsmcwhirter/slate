CREATE TABLE IF NOT EXISTS sysops(
	sysop_id BIGINT unsigned not null auto_increment,
	PRIMARY KEY(sysop_id),
	u_id BIGINT unsigned not null,
	UNIQUE KEY(u_id),
	sysop_accesslevel TINYINT(1) unsigned not null
) ENGINE=InnoDB;
