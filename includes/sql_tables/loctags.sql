CREATE TABLE IF NOT EXISTS loctags(
  id TINYINT unsigned not null auto_increment,
  PRIMARY KEY(id),
  label VARCHAR(255) not null,
  max_concurrent_appts TINYINT unsigned not null DEFAULT 0
);
