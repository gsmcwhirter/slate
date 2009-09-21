CREATE TABLE IF NOT EXISTS consultanthoursrequests(
    consultant_id MEDIUMINT unsigned not null,
    hoursrequest_id INT unsigned not null,
    KEY(consultant_id),
    KEY(hoursrequest_id)
);

DROP TABLE IF EXISTS consultanthoursrequests;
