CREATE TABLE IF NOT EXISTS ophours(
	id INT unsigned not null auto_increment,
	PRIMARY KEY(id),
	appttype_id TINYINT unsigned not null,
	starttime TIME not null,
	stoptime TIME not null,
	startdate DATE not null,
	stopdate DATE not null,
	repetition SET('M','T','W','H','F','S','N') not null default 'M,T,W,H,F',	
	special ENUM('regular','delete') not null default 'regular',
  timestamp INT unsigned not null, 
	KEY(startdate),
	KEY(stopdate),
	KEY(appttype_id)
);
