CREATE TABLE IF NOT EXISTS reports(
  id MEDIUMINT unsigned not null auto_increment,
  PRIMARY KEY(id),
  description TINYTEXT not null,
  parameters TEXT not null
);