CREATE TABLE IF NOT EXISTS reports(
  r_id MEDIUMINT unsigned not null auto_increment,
  PRIMARY KEY(r_id),
  r_description TINYTEXT not null,
  r_parameters TEXT not null
) ENGINE=InnoDB;
