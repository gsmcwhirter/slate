CREATE TABLE IF NOT EXISTS tickets(
  t_id BIGINT unsigned not null auto_increment,
  PRIMARY KEY(t_id),
  t_client tinytext not null,
  t_phone1 VARCHAR(20) not null,
  t_phone2 VARCHAR(20) not null,
  t_remedy_ticket VARCHAR(15) not null,
  t_description TEXT not null
);
