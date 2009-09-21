CREATE TABLE IF NOT EXISTS location_loctags(
  loctag_id TINYINT unsigned not null,
  location_id MEDIUMINT unsigned not null,
  KEY(loctag_id), 
  KEY(location_id)
);
