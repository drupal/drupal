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

# 25/05/2001  - TEMPORARY - UNDER HEAVY CHANGE -

CREATE TABLE entry (
  eid int(10) unsigned DEFAULT '0' NOT NULL auto_increment,
  name varchar(32) DEFAULT '' NOT NULL,
  keyword varchar(255) DEFAULT '' NOT NULL,
  collection varchar(32) DEFAULT '' NOT NULL,
  UNIQUE name (name, collection),
  PRIMARY KEY (eid)
);

CREATE TABLE bundle (
  bid int(11) DEFAULT '0' NOT NULL auto_increment,
  title varchar(255) DEFAULT '' NOT NULL,
  attribute varchar(255) DEFAULT '' NOT NULL,
  UNIQUE (title),
  PRIMARY KEY (bid)
);

CREATE TABLE feed (
  fid int(11) DEFAULT '0' NOT NULL auto_increment,
  title varchar(255) DEFAULT '' NOT NULL,
  link varchar(255) DEFAULT '' NOT NULL,
  refresh int(11),
  uncache int(11),
  timestamp int(11),
  attribute varchar(255) DEFAULT '' NOT NULL,
  UNIQUE (title),
  UNIQUE (link),
  PRIMARY KEY (fid)
);

CREATE TABLE item (
  iid int(11) DEFAULT '0' NOT NULL auto_increment,
  fid int(11) DEFAULT '0' NOT NULL,
  title varchar(255) DEFAULT '' NOT NULL,
  link varchar(255) DEFAULT '' NOT NULL,
  author varchar(255) DEFAULT '' NOT NULL,
  description TEXT DEFAULT '' NOT NULL,
  timestamp int(11),
  attribute varchar(255) DEFAULT '' NOT NULL,
  PRIMARY KEY (iid)
);

# 31/05/01

CREATE TABLE poll (
  lid int(10) unsigned DEFAULT '0' NOT NULL auto_increment,
  nid int(10) unsigned DEFAULT '0' NOT NULL,
  runtime int(10) DEFAULT '0' NOT NULL,
  voters text NOT NULL,
  active int(2) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY (lid)
);

CREATE TABLE poll_choices (
  chid int(10) unsigned DEFAULT '0' NOT NULL auto_increment,
  nid int(10) unsigned DEFAULT '0' NOT NULL,
  chtext varchar(128) DEFAULT '' NOT NULL,
  chvotes int(6) DEFAULT '0' NOT NULL,
  chorder int(2) DEFAULT '0' NOT NULL,
  PRIMARY KEY (chid)
);

# 04/06/01

ALTER TABLE node ADD timestamp_posted int(11) NOT NULL;
ALTER TABLE node ADD timestamp_queued int(11) NOT NULL;
ALTER TABLE node ADD timestamp_hidden int(11) NOT NULL;
ALTER TABLE node ADD attribute varchar(255) DEFAULT '' NOT NULL;

# 10/06/01
ALTER TABLE node DROP cid;
ALTER TABLE node DROP tid;

# 11/06/01
UPDATE users SET access = REPLACE(access, ':', '=');
UPDATE users SET access = REPLACE(access, ';', ',');
UPDATE comments SET users = REPLACE(users, ';', ',');
UPDATE comments SET users = REPLACE(users, ':', '=');
UPDATE node SET users = REPLACE(users, ';', ',');
UPDATE node SET users = REPLACE(users, ':', '=');
UPDATE node SET attributes = REPLACE(attributes, ';', ',');
UPDATE node SET attributes = REPLACE(attributes, ':', '=');
UPDATE entry SET attributes = REPLACE(attributes, ';', ',');
UPDATE entry SET attributes = REPLACE(attributes, ':', '=');

ALTER TABLE entry CHANGE keyword attributes varchar(255) DEFAULT '' NOT NULL;
ALTER TABLE node CHANGE attribute attributes varchar(255) DEFAULT '' NOT NULL;
ALTER TABLE bundle CHANGE attribute attributes varchar(255) DEFAULT '' NOT NULL;
ALTER TABLE feed CHANGE attribute attributes varchar(255) DEFAULT '' NOT NULL;
ALTER TABLE item CHANGE attribute attributes varchar(255) DEFAULT '' NOT NULL;

# 12/06/01
ALTER TABLE watchdog DROP link;

# 15/06/01
CREATE TABLE tag (
  tid int(10) unsigned DEFAULT '0' NOT NULL auto_increment,
  name varchar(32) DEFAULT '' NOT NULL,
  attributes varchar(255) DEFAULT '' NOT NULL,
  collections varchar(32) DEFAULT '' NOT NULL,
  UNIQUE name (name, collections),
  PRIMARY KEY (tid)
);

CREATE TABLE collection (
  cid int(10) unsigned DEFAULT '0' NOT NULL auto_increment,
  name varchar(32) DEFAULT '' NOT NULL,
  types varchar(128) DEFAULT '' NOT NULL,
  UNIQUE name (name),
  PRIMARY KEY (cid)
);

# 17/06/01
ALTER TABLE book ADD pid int(10) DEFAULT '0' NOT NULL;
ALTER TABLE book ADD log text NOT NULL;
ALTER TABLE node DROP pid;
ALTER TABLE node DROP log;
DROP TABLE headlines;

# 20/06/01
CREATE TABLE role (
  rid int(10) unsigned DEFAULT '0' NOT NULL auto_increment,
  name varchar(32) DEFAULT '' NOT NULL,
  perm text DEFAULT '' NOT NULL,
  UNIQUE name (name),
  PRIMARY KEY (rid)
);

ALTER TABLE users ADD role varchar(32) DEFAULT '' NOT NULL;
ALTER TABLE users DROP access;
UPDATE users SET role = 'authenticated user';

# 23/06/01
ALTER TABLE users CHANGE userid userid VARCHAR(32) DEFAULT '' NOT NULL;

# 24/06/01
CREATE TABLE referer (
  url varchar(255) DEFAULT '' NOT NULL,
  timestamp int(11) NOT NULL
);

# 30/06/01
ALTER TABLE boxes CHANGE subject title varchar(64) DEFAULT '' NOT NULL;
ALTER TABLE boxes CHANGE content body TEXT;
ALTER TABLE boxes CHANGE id bid tinyint(4) DEFAULT '0' NOT NULL auto_increment;

CREATE TABLE cache (
  url varchar(255) DEFAULT '' NOT NULL,
  data text NOT NULL,
  timestamp int(11) NOT NULL,
  PRIMARY KEY (url)
);

# 08/06/01
CREATE TABLE site (
  sid int(10) unsigned DEFAULT '0' NOT NULL auto_increment,
  title varchar(128) DEFAULT '' NOT NULL,
  url varchar(255) DEFAULT '' NOT NULL,
  size text NOT NULL,
  timestamp int(11) NOT NULL,
  UNIQUE (title),
  UNIQUE (url),
  PRIMARY KEY (sid)
);
