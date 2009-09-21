CREATE TABLE IF NOT EXISTS sysops(
  username VARCHAR(25) not null,
  PRIMARY KEY(username),
  accesslevel TINYINT(1) unsigned not null
);
