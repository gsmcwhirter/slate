CREATE TABLE IF NOT EXISTS consultant_consultant_tags(
	ct_id TINYINT unsigned not null,
	c_id BIGINT unsigned not null,
	KEY(c_id),
	cct_primary ENUM('true','false') not null default 'false'
	KEY(cct_primary)
) ENGINE=InnoDB;
