CREATE TABLE IF NOT EXISTS semesters(
  id INT unsigned not null auto_increment,
  PRIMARY KEY(id),
  name TEXT not null,
  startdate DATE not null,
  stopdate DATE not null
);