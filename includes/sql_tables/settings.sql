CREATE TABLE IF NOT EXISTS settings(
  id VARCHAR(255) not null UNIQUE,
  PRIMARY KEY(id),
  svalue VARCHAR(255) not null,
  svaluetype ENUM('string','boolean','integer','float','aphash') not null
);