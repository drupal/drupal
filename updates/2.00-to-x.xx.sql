# 05/04/2001:
CREATE TABLE variable (
  name varchar(32) DEFAULT '' NOT NULL,
  value varchar(128) DEFAULT '' NOT NULL,
  PRIMARY KEY (name)
);

CREATE TABLE watchdog (
  id int(5) DEFAULT '0' NOT NULL auto_increment,
  user int(6) DEFAULT '0' NOT NULL,
  type varchar(16) DEFAULT '' NOT NULL,
  link varchar(16) DEFAULT '' NOT NULL,
  message varchar(255) DEFAULT '' NOT NULL,
  location varchar(128) DEFAULT '' NOT NULL,
  hostname varchar(128) DEFAULT '' NOT NULL,
  timestamp int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (id)
);

# 01/04/2001:

CREATE TABLE access (
  id tinyint(10) DEFAULT '0' NOT NULL auto_increment,
  mask varchar(255) DEFAULT '' NOT NULL,
  reason text NOT NULL,
  UNIQUE mask (mask),
  PRIMARY KEY (id)
);

CREATE TABLE book (
  lid int(10) unsigned DEFAULT '0' NOT NULL auto_increment,
  nid int(10) unsigned DEFAULT '0' NOT NULL,
  body text NOT NULL,
  section int(10) DEFAULT '0' NOT NULL,
  parent int(10) DEFAULT '0' NOT NULL,
  weight tinyint(3) DEFAULT '0' NOT NULL,
  PRIMARY KEY (lid)
);

CREATE TABLE story (
  lid int(10) unsigned DEFAULT '0' NOT NULL auto_increment,
  nid int(10) unsigned DEFAULT '0' NOT NULL,
  abstract text NOT NULL,
  body text NOT NULL,
  section varchar(64) DEFAULT '' NOT NULL,
  PRIMARY KEY (lid)
);

CREATE TABLE node (
  nid int(10) unsigned DEFAULT '0' NOT NULL auto_increment,
  lid int(10) DEFAULT '0' NOT NULL,
  pid int(10) DEFAULT '0' NOT NULL,
  log text NOT NULL,
  type varchar(16) DEFAULT '' NOT NULL,
  title varchar(128) DEFAULT '' NOT NULL,
  score int(11) DEFAULT '0' NOT NULL,
  votes int(11) DEFAULT '0' NOT NULL,
  author int(6) DEFAULT '0' NOT NULL,
  status int(4) DEFAULT '1' NOT NULL,
  timestamp int(11) NOT NULL,
  KEY type (lid, type),
  KEY author (author),
  KEY title (title, type),
  PRIMARY KEY (nid)
);

alter table users change stories nodes tinyint(2) DEFAULT '10';
alter table comments drop link;
