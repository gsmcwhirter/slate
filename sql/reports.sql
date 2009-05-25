CREATE TABLE IF NOT EXISTS reports(
  rep_id MEDIUMINT unsigned not null auto_increment,
  PRIMARY KEY(r_id),
  rep_description TINYTEXT not null,
  rep_parameters TEXT not null
) ENGINE=InnoDB;
