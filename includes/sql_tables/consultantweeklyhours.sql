CREATE TABLE IF NOT EXISTS consultantweeklyhours(
  week_date DATE not null,
  consultant_id MEDIUMINT unsigned not null,
  week_hours FLOAT(3,1),
  UNIQUE KEY(week_date, consultant_id)
);
