CREATE TABLE IF NOT EXISTS consultant_weeklyhours(
  cwh_date DATE not null,
  c_id BIGINT unsigned not null,
  cwh_hours FLOAT(3,1),
  UNIQUE KEY(cwh_date, c_id)
) ENGINE=InnoDB;
