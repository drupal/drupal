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

# 07/04/2001:
CREATE TABLE page (
  lid int(10) unsigned DEFAULT '0' NOT NULL auto_increment,
  nid int(10) unsigned DEFAULT '0' NOT NULL,
  body text NOT NULL,
  format tinyint(2) DEFAULT '0' NOT NULL,
  PRIMARY KEY (lid)
);

CREATE TABLE variable (
  name varchar(32) DEFAULT '' NOT NULL,
  value text DEFAULT '' NOT NULL,
  PRIMARY KEY (name)
);

CREATE TABLE rating (
  user int(6) DEFAULT '0' NOT NULL,
  new int(6) DEFAULT '0' NOT NULL,
  old int(6) DEFAULT '0' NOT NULL,
  PRIMARY KEY (user)
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

ALTER TABLE users CHANGE rating rating decimal(8,2);

# 14/04/2001:
ALTER TABLE node ADD cid int(10) unsigned DEFAULT '0' NOT NULL;
ALTER TABLE node ADD tid int(10) unsigned DEFAULT '0' NOT NULL;
ALTER TABLE story DROP section;
ALTER TABLE comments ADD KEY(lid);

CREATE TABLE category (
  cid int(10) unsigned DEFAULT '0' NOT NULL auto_increment,
  name varchar(32) DEFAULT '' NOT NULL,
  type varchar(16) DEFAULT '' NOT NULL,
  post int(3) DEFAULT '0' NOT NULL,
  dump int(3) DEFAULT '0' NOT NULL,
  expire int(3) DEFAULT '0' NOT NULL,
  comment int(2) unsigned DEFAULT '0' NOT NULL,
  submission int(2) unsigned DEFAULT '0' NOT NULL,
  UNIQUE (name),
  PRIMARY KEY (cid)
);

CREATE TABLE topic (
  tid int(10) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(10) unsigned DEFAULT '0' NOT NULL,
  name varchar(32) DEFAULT '' NOT NULL,
  UNIQUE (name),
  PRIMARY KEY (tid)
);

# 19/04/2001:
ALTER TABLE node ADD comment int(2) DEFAULT '1' NOT NULL;
ALTER TABLE node ADD promote int(2) DEFAULT '1' NOT NULL;
ALTER TABLE category ADD promote int(2) unsigned DEFAULT '0' NOT NULL;

CREATE TABLE cvs (
  user varchar(32) DEFAULT '' NOT NULL,
  files text,
  status int(2) DEFAULT '0' NOT NULL,
  message text,
  timestamp int(11) DEFAULT '0' NOT NULL
);

# 27/04/2001:
CREATE TABLE forum (
  lid int(10) unsigned DEFAULT '0' NOT NULL auto_increment,
  nid int(10) unsigned DEFAULT '0' NOT NULL,
  body text NOT NULL,
  PRIMARY KEY (lid)
);

# 01/05/2001:
ALTER TABLE node ADD moderate TEXT NOT NULL;

# 10/05/2001:
ALTER TABLE topic ADD moderate TEXT NOT NULL;

# 16/05/2001
ALTER TABLE node ADD users TEXT NOT NULL;
ALTER TABLE comments ADD users TEXT NOT NULL;
ALTER TABLE users DROP history;

# 19/05/2001
DROP TABLE crons;
