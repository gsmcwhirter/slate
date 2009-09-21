CREATE TABLE IF NOT EXISTS hoursrequests(
  id INT unsigned not null auto_increment,
  PRIMARY KEY(id),
  initiated ENUM('staff','user') not null default 'staff',
  startdate DATE not null,
  stopdate DATE not null,
  total_hours_x2 TINYINT unsigned not null,
  using_hours_x2 TINYINT unsigned not null
);

DROP TABLE IF EXISTS hoursrequests;