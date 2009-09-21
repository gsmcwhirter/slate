CREATE TABLE IF NOT EXISTS appthours(
  id BIGINT unsigned not null auto_increment,
  PRIMARY KEY(id),
  appttype_id TINYINT unsigned not null,
  starttime TIME not null,
  stoptime TIME not null,
  KEY(starttime, stoptime),
  repeat SET('M','T','W','H','F','S','N') not null default '',
  startdate DATE not null,
  stopdate DATE not null,
  htype ENUM('repeat','once','delete') not null default 'repeat',
  timestamp INT unsigned not null,
  KEY(appttype_id),
  KEY(startdate),
  KEY(stopdate)
);
