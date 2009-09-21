CREATE TABLE IF NOT EXISTS meetings(
  id BIGINT unsigned not null auto_increment,
  PRIMARY KEY(id),
  subject VARCHAR(255) not null,
  description TEXT not null
);