
ALTER TABLE boxes DROP link;

ALTER TABLE users RENAME AS user;
ALTER TABLE user DROP INDEX real_email;
ALTER TABLE user DROP fake_email;
ALTER TABLE user DROP nodes;
ALTER TABLE user DROP bio;
ALTER TABLE user DROP hash;
ALTER TABLE user ADD session varchar(32) DEFAULT '' NOT NULL;
ALTER TABLE user ADD jabber varchar(128) DEFAULT '' NULL;
ALTER TABLE user ADD drupal varchar(128) DEFAULT '' NULL;
ALTER TABLE user ADD init varchar(64) DEFAULT '' NULL;
ALTER TABLE user CHANGE passwd pass varchar(24) DEFAULT '' NOT NULL;
ALTER TABLE user CHANGE real_email mail varchar(64) DEFAULT '' NULL;
ALTER TABLE user CHANGE last_access timestamp int(11) DEFAULT '0' NOT NULL;
ALTER TABLE user CHANGE last_host hostname varchar(128) DEFAULT '' NOT NULL;
ALTER TABLE user CHANGE id uid int(10) unsigned DEFAULT '0' NOT NULL auto_increment;
ALTER TABLE user CHANGE url homepage varchar(128) DEFAULT '' NOT NULL;
UPDATE user SET status = 1 WHERE status = 2;
UPDATE user SET name = userid;
ALTER TABLE user DROP userid;
UPDATE user SET init = mail;

DROP TABLE access;

CREATE TABLE access (
  aid tinyint(10) DEFAULT '0' NOT NULL auto_increment,
  mask varchar(255) DEFAULT '' NOT NULL,
  type varchar(255) DEFAULT '' NOT NULL,
  status tinyint(2) DEFAULT '0' NOT NULL,
  UNIQUE mask (mask),
  PRIMARY KEY (aid)
);

CREATE TABLE moderate (
  cid int(10) DEFAULT '0' NOT NULL,
  nid int(10) DEFAULT '0' NOT NULL,
  uid int(10) DEFAULT '0' NOT NULL,
  score int(2) DEFAULT '0' NOT NULL,
  timestamp int(11) DEFAULT '0' NOT NULL,
  INDEX (cid),
  INDEX (nid)
);

ALTER TABLE comments DROP score;
ALTER TABLE comments DROP votes;
ALTER TABLE comments DROP users;

# PEAR

ALTER TABLE user RENAME AS users;
ALTER TABLE users CHANGE pass pass varchar(32) DEFAULT '' NOT NULL;
ALTER TABLE watchdog CHANGE user userid int(10) DEFAULT '0' NOT NULL;
ALTER TABLE rating CHANGE user userid int(10) DEFAULT '0' NOT NULL;
ALTER TABLE layout CHANGE user userid int(10) DEFAULT '0' NOT NULL;
ALTER TABLE blocks CHANGE offset delta tinyint(2) DEFAULT '0' NOT NULL;

# 14/10/01

ALTER TABLE watchdog CHANGE id wid int(5) DEFAULT '0' NOT NULL auto_increment;
ALTER TABLE watchdog CHANGE userid uid int(10) DEFAULT '0' NOT NULL;
ALTER TABLE layout CHANGE userid uid int(10) DEFAULT '0' NOT NULL;
ALTER TABLE rating CHANGE userid uid int(10) DEFAULT '0' NOT NULL;
ALTER TABLE locales CHANGE id lid int(10) DEFAULT '0' NOT NULL;
