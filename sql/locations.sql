CREATE TABLE IF NOT EXISTS locations(
  l_id MEDIUMINT unsigned not null auto_increment,
  PRIMARY KEY(l_id),
  l_name tinytext not null,
  l_restrict_gender ENUM('m','f','false') default 'false'
) ENGINE=InnoDB;
