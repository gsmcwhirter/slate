CREATE TABLE IF NOT EXISTS metaloc_locations(
  metaloc_id INT unsigned not null,
  location_id MEDIUMINT unsigned not null,
  KEY(metaloc_id, location_id)
);