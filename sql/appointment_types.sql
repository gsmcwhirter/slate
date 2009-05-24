CREATE TABLE IF NOT EXISTS appointment_types(
  at_id TINYINT unsigned not null auto_increment,
  PRIMARY KEY(at_id),
  at_name tinytext not null,
  at_ticket_or_meeting ENUM('meeting','ticket') not null default 'ticket',
  at_loc_id_default MEDIUMINT unsigned not null default 0,
  at_loc_details_default VARCHAR(255) not null,
  at_max_concurrent TINYINT unsigned not null default 0,
  at_weekdays_allowed SET('M','T','W','H','F','S','N') not null default 'M,T,W,H,F,S,N',
  at_min_length TINYINT unsigned not null default 3
) ENGINE=InnoDB;
