CREATE TABLE IF NOT EXISTS settings(
  set_id VARCHAR(255) not null UNIQUE,
  PRIMARY KEY(set_id),
  set_value VARCHAR(255) not null,
  set_type ENUM('string','boolean','integer','float','aphash') not null
);
