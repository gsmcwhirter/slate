CREATE TABLE IF NOT EXISTS appttypes(
  id TINYINT unsigned not null auto_increment,
  PRIMARY KEY(id),
  name VARCHAR(255) not null,
  tm_class ENUM('Meeting,Meecket','Ticket') not null default 'Ticket',
  default_loc MEDIUMINT unsigned not null default 0,
  default_locdetails VARCHAR(255) not null,
  max_concurrent_appts TINYINT unsigned not null default 0,
  weekdays_allowed SET('M','T','W','H','F','S','N') not null default 'M,T,W,H,F,S,N',
  min_appt_length TINYINT unsigned not null default 3
);