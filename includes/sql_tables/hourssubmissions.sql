CREATE TABLE IF NOT EXISTS hourssubmissions(
    id BIGINT unsigned not null auto_increment,
    PRIMARY KEY(id),
    consultant_id MEDIUMINT unsigned not null,
    KEY(consultant_id),
    hoursrequest_id INT unsigned not null,
    KEY(hoursrequest_id),
    weekdays SET('M','T','W','H','F','S','N') not null default '',
    starttime TIME not null,
    stoptime TIME not null,
    ondate DATE not null,
    type ENUM('repeat','off','replace') not null default 'repeat',
    submitted_on INT unsigned not null
);

DROP TABLE IF EXISTS hourssubmissions;
