CREATE TABLE IF NOT EXISTS meetings(
  m_id BIGINT unsigned not null auto_increment,
  PRIMARY KEY(m_id),
  m_subject tinytext not null,
  m_description TEXT not null
) ENGINE=InnoDB;
