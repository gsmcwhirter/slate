CREATE TABLE IF NOT EXISTS tickets(
  id BIGINT unsigned not null auto_increment,
  PRIMARY KEY(id),
  person VARCHAR(255) not null,
  phone VARCHAR(20) not null,
  altphone VARCHAR(20) not null,
  remedy_ticket VARCHAR(15) not null,
  description TEXT not null
);