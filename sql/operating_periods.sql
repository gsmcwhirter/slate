CREATE TABLE IF NOT EXISTS operating_periods(
  op_id INT unsigned not null auto_increment,
  PRIMARY KEY(op_id),
  op_name TEXT not null,
  op_startdate DATE not null,
  op_stopdate DATE not null
) ENGINE=InnoDB;
