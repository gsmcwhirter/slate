CREATE TABLE IF NOT EXISTS location_location_tags(
  lt_id TINYINT unsigned not null,
  l_id MEDIUMINT unsigned not null,
  KEY(lt_id),
  KEY(l_id)
) ENGINE=InnoDB;
