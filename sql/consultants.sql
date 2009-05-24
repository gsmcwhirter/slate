CREATE TABLE IF NOT EXISTS consultants(
	c_id BIGINT unsigned not null auto_increment,
	PRIMARY KEY(c_id)
	u_id BIGINT unsigned not null,
	UNIQUE KEY(u_id),
	c_gender ENUM('M','F') not null default 'M',
	c_staff ENUM('TRUE','FALSE') not null default 'FALSE',
	c_appt_perms CHAR(12) not null default '111111111111',
	KEY(c_staff),
	KEY(ct_id),
	c_pref_send_text ENUM('yes','no') not null default 'no',
	c_pref_text_address tinytext not null
) ENGINE=InnoDB;
