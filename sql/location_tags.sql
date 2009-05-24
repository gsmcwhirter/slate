CREATE TABLE IF NOT EXISTS location_tags(
  lt_id TINYINT unsigned not null auto_increment,
  PRIMARY KEY(lt_id),
  lt_name tinytext not null,
  lt_max_concurrent_appts TINYINT unsigned not null DEFAULT 0
) ENGINE=InnoDB;
