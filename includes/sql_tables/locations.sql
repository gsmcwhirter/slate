CREATE TABLE IF NOT EXISTS locations(
  id MEDIUMINT unsigned not null auto_increment,
  PRIMARY KEY(id),
  name VARCHAR(255) not null,
  appttype_id TINYINT unsigned not null,
  loczone_id TINYINT unsigned not null,
  restrict_gender ENUM('M','F','FALSE') default 'FALSE',
  metaloc_id INT unsigned not null,
  KEY(appttype_id),
  KEY(loczone_id),
  KEY(metaloc_id)
);