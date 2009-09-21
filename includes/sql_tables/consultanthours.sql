CREATE TABLE IF NOT EXISTS consultanthours(
  id BIGINT unsigned not null auto_increment,
  PRIMARY KEY(id),
  consultant_id MEDIUMINT unsigned not null,
  starttime TIME not null,
  stoptime TIME not null,
  KEY(starttime, stoptime),
  repeat SET('M','T','W','H','F','S','N') not null default '',
  startdate DATE not null,
  stopdate DATE not null,
  htype ENUM('repeat','once','delete') not null default 'repeat',
  htype2 ENUM('request','regular') not null default 'regular',
  hourrequest_id INT unsigned not null,
  oncall ENUM('TRUE','FALSE') not null default 'FALSE',
  timestamp INT unsigned not null,
  KEY(consultant_id),
  KEY(startdate),
  KEY(stopdate)
) Type=InnoDB;
