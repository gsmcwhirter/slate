CREATE TABLE IF NOT EXISTS tags(
	id TINYINT unsigned not null auto_increment,
	PRIMARY KEY(id),
	label varchar(255) not null,
	color CHAR(6) not null
);
