CREATE TABLE IF NOT EXISTS metalocs(
  id INT unsigned not null auto_increment,
  PRIMARY KEY(id),
  name VARCHAR(255),
  universal ENUM('TRUE','FALSE') not null default 'FALSE'
);