CREATE TABLE IF NOT EXISTS loczones(
	id TINYINT unsigned not null auto_increment,
	PRIMARY KEY(id),
	name VARCHAR(255) not null,
	potentialh TINYINT not null,
	potentialv TINYINT not null
);
