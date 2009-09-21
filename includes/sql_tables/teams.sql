CREATE TABLE IF NOT EXISTS teams(
  id TINYINT unsigned not null auto_increment,
  PRIMARY KEY(id),
  supervisor_id TINYINT unsigned not null,
  name VARCHAR(255) not null
);

DROP TABLE IF EXISTS teams;
