-- PostgreSQL include file 31/10/2002
-- Maintainer: James Arthur, j_a_arthurATyahooDOTcom

--
-- Table structure for access
--

CREATE TABLE access (
  aid SERIAL,
  mask varchar(255) NOT NULL default '',
  type varchar(255) NOT NULL default '',
  status smallint NOT NULL default '0',
  PRIMARY KEY (aid),
  UNIQUE (mask)
);

--
-- Table structure for accesslog
--

CREATE TABLE accesslog (
  nid integer default '0',
  url varchar(255) default NULL,
  hostname varchar(128) default NULL,
  uid integer default '0',
  timestamp integer NOT NULL default '0'
);

--
-- Table structure for authmap
--

CREATE TABLE authmap (
  aid SERIAL,
  uid integer NOT NULL default '0',
  authname varchar(128) NOT NULL default '',
  module varchar(128) NOT NULL default '',
  PRIMARY KEY (aid),
  UNIQUE (authname)
);

--
-- Table structure for blocks
--

CREATE TABLE blocks (
  module varchar(64) NOT NULL default '',
  delta smallint NOT NULL default '0',
  status smallint NOT NULL default '0',
  weight smallint NOT NULL default '0',
  region smallint NOT NULL default '0',
  path varchar(255) NOT NULL default '',
  custom smallint NOT NULL default '0'
);

--
-- Table structure for book
--

CREATE TABLE book (
  nid integer NOT NULL default '0',
  parent integer NOT NULL default '0',
  weight smallint NOT NULL default '0',
  format smallint default '0',
  log text default '',
  PRIMARY KEY (nid)
);
CREATE INDEX book_nid_idx ON book(nid);

--
-- Table structure for boxes
--

CREATE TABLE boxes (
  bid SERIAL,
  title varchar(64) NOT NULL default '',
  body text default '',
  info varchar(128) NOT NULL default '',
  type smallint NOT NULL default '0',
  PRIMARY KEY  (bid),
  UNIQUE (info),
  UNIQUE (title)
);

--
-- Table structure for bundle
--

CREATE TABLE bundle (
  bid SERIAL,
  title varchar(255) NOT NULL default '',
  attributes varchar(255) NOT NULL default '',
  PRIMARY KEY  (bid),
  UNIQUE (title)
);

--
-- Table structure for cache
--

CREATE TABLE cache (
  cid varchar(255) NOT NULL default '',
  data text default '',
  expire integer NOT NULL default '0',
  PRIMARY KEY  (cid)
);

--
-- Table structure for comments
--

CREATE TABLE comments (
  cid SERIAL,
  pid integer NOT NULL default '0',
  nid integer NOT NULL default '0',
  uid integer NOT NULL default '0',
  subject varchar(64) NOT NULL default '',
  comment text NOT NULL default '',
  hostname varchar(128) NOT NULL default '',
  timestamp integer NOT NULL default '0',
  link varchar(16) NOT NULL default '',
  score integer NOT NULL default '0',
  status smallint  NOT NULL default '0',
  users text default '',
  PRIMARY KEY  (cid)
);
CREATE INDEX comments_nid_idx ON comments(nid);

--
-- Table structure for directory
--

CREATE TABLE directory (
  link varchar(255) NOT NULL default '',
  name varchar(128) NOT NULL default '',
  mail varchar(128) NOT NULL default '',
  slogan text NOT NULL default '',
  mission text NOT NULL default '',
  timestamp integer NOT NULL default '0',
  PRIMARY KEY  (link)
);

--
-- Table structure for feed
--

CREATE TABLE feed (
  fid SERIAL,
  title varchar(255) NOT NULL default '',
  url varchar(255) NOT NULL default '',
  refresh integer NOT NULL default '0',
  timestamp integer NOT NULL default '0',
  attributes varchar(255) NOT NULL default '',
  link varchar(255) NOT NULL default '',
  description text NOT NULL default '',
  PRIMARY KEY  (fid),
  UNIQUE (title),
  UNIQUE (url)
);

--
-- Table structure for table 'forum'
--

CREATE TABLE forum (
  nid integer NOT NULL default '0',
  icon varchar(255) NOT NULL default '',
  shadow integer NOT NULL default '0',
  PRIMARY KEY  (nid)
);

--
-- Table structure for history
--

CREATE TABLE history (
  uid integer NOT NULL default '0',
  nid integer NOT NULL default '0',
  timestamp integer NOT NULL default '0',
  PRIMARY KEY  (uid,nid)
);

--
-- Table structure for item
--

CREATE TABLE item (
  iid SERIAL,
  fid integer NOT NULL default '0',
  title varchar(255) NOT NULL default '',
  link varchar(255) NOT NULL default '',
  author varchar(255) NOT NULL default '',
  description text NOT NULL default '',
  timestamp integer NOT NULL default '0',
  attributes varchar(255) NOT NULL default '',
  PRIMARY KEY  (iid)
);

--
-- Table structure for locales
--

CREATE TABLE locales (
  lid SERIAL,
  location varchar(128) NOT NULL default '',
  string text NOT NULL default '',
  da text NOT NULL default '',
  fi text NOT NULL default '',
  fr text NOT NULL default '',
  en text NOT NULL default '',
  es text NOT NULL default '',
  nl text NOT NULL default '',
  no text NOT NULL default '',
  sw text NOT NULL default '',
  PRIMARY KEY  (lid)
);

--
-- Table structure for table 'moderation_filters'
--

CREATE TABLE moderation_filters (
  fid SERIAL,
  filter varchar(255) NOT NULL default '',
  minimum smallint NOT NULL default '0',
  PRIMARY KEY  (fid)
);

--
-- Table structure for table 'moderation_roles'
--

CREATE TABLE moderation_roles (
  rid integer NOT NULL default '0',
  mid integer NOT NULL default '0',
  value smallint NOT NULL default '0'
);
CREATE INDEX moderation_roles_rid_idx ON moderation_roles(rid);
CREATE INDEX moderation_roles_mid_idx ON moderation_roles(mid);

--
-- Table structure for table 'moderation_votes'
--

CREATE TABLE moderation_votes (
  mid SERIAL,
  vote varchar(255) default NULL,
  weight smallint NOT NULL default '0',
  PRIMARY KEY  (mid)
);

--
-- Table structure for node
--

CREATE TABLE node (
  nid SERIAL,
  type varchar(16) NOT NULL default '',
  title varchar(128) NOT NULL default '',
  score integer NOT NULL default '0',
  votes integer NOT NULL default '0',
  uid integer NOT NULL default '0',
  status integer NOT NULL default '1',
  created integer NOT NULL default '0',
  comment integer NOT NULL default '0',
  promote integer NOT NULL default '0',
  moderate integer NOT NULL default '0',
  users text NOT NULL default '',
  attributes varchar(255) NOT NULL default '',
  teaser text NOT NULL default '',
  body text NOT NULL default '',
  changed integer NOT NULL default '0',
  revisions text NOT NULL default '',
  static integer NOT NULL default '0',
  PRIMARY KEY  (nid)
);
CREATE INDEX node_type_idx ON node(type);
CREATE INDEX node_title_idx ON node(title,type);
CREATE INDEX node_promote_idx ON node(promote);
CREATE INDEX node_status_idx ON node(status);
CREATE INDEX node_uid_idx ON node(uid);

--
-- Table structure for page
--

CREATE TABLE page (
  nid integer NOT NULL default '0',
  link varchar(128) NOT NULL default '',
  format smallint NOT NULL default '0',
  description varchar(128) NOT NULL default '',
  PRIMARY KEY  (nid)
);
CREATE INDEX page_nid_idx ON page(nid);

--
-- Table structure for permission
--

CREATE TABLE permission (
  rid integer NOT NULL default '0',
  perm text default '',
  tid integer NOT NULL default '0'
);
CREATE INDEX permission_rid_idx ON permission(rid);

--
-- Table structure for poll
--

CREATE TABLE poll (
  nid integer NOT NULL default '0',
  runtime integer NOT NULL default '0',
  voters text NOT NULL default '',
  active integer NOT NULL default '0',
  PRIMARY KEY  (nid)
);

--
-- Table structure for poll_choices
--

CREATE TABLE poll_choices (
  chid SERIAL,
  nid integer NOT NULL default '0',
  chtext varchar(128) NOT NULL default '',
  chvotes integer NOT NULL default '0',
  chorder integer NOT NULL default '0',
  PRIMARY KEY  (chid)
);
CREATE INDEX poll_choices_nid_idx ON poll_choices(nid);

--
-- Table structure for role
--

CREATE TABLE role (
  rid SERIAL,
  name varchar(32) NOT NULL default '',
  PRIMARY KEY  (rid),
  UNIQUE (name)
);

--
-- Table structure for search_index
--

CREATE TABLE search_index (
  word varchar(50) NOT NULL default '',
  lno integer NOT NULL default '0',
  type varchar(16) default NULL,
  count integer default NULL
);
CREATE INDEX search_index_lno_idx ON search_index(lno);
CREATE INDEX search_index_word_idx ON search_index(word);

--
-- Table structure for sequences
-- This is only used under MySQL, co commented out
--
--
-- CREATE TABLE sequences (
--   name varchar(255) NOT NULL,
--   id integer NOT NULL,
--   PRIMARY KEY (name)
-- );

--
-- Table structure for site
--

CREATE TABLE site (
  sid SERIAL,
  name varchar(128) NOT NULL default '',
  link varchar(255) NOT NULL default '',
  size text NOT NULL default '',
  timestamp integer NOT NULL default '0',
  feed varchar(255) NOT NULL default '',
  refresh integer NOT NULL default '0',
  threshold integer NOT NULL default '0',
  PRIMARY KEY  (sid),
  UNIQUE (name),
  UNIQUE (link)
);

--
-- Table structure for table 'statistics'
--

CREATE TABLE statistics (
  nid integer NOT NULL default '0',
  totalcount integer NOT NULL default '0',
  daycount integer NOT NULL default '0',
  timestamp integer NOT NULL default '0',
  PRIMARY KEY  (nid)
);
CREATE INDEX statistics_totalcount_idx ON statistics(totalcount);
CREATE INDEX statistics_daycount_idx ON statistics(daycount);
CREATE INDEX statistics_timestamp_idx ON statistics(timestamp);

--
-- Table structure for system
--

CREATE TABLE system (
  filename varchar(255) NOT NULL default '',
  name varchar(255) NOT NULL default '',
  type varchar(255) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  status integer NOT NULL default '0',
  PRIMARY KEY  (filename)
);



--
-- Table structure for term_data
--

CREATE TABLE term_data (
  tid SERIAL,
  vid integer NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  description text default '',
  weight smallint NOT NULL default '0',
  PRIMARY KEY  (tid)
);
CREATE INDEX term_data_vid_idx ON term_data(vid);

--
-- Table structure for term_hierarchy
--

CREATE TABLE term_hierarchy (
  tid integer NOT NULL default '0',
  parent integer NOT NULL default '0'
);
CREATE INDEX term_hierarchy_tid_idx ON term_hierarchy(tid);
CREATE INDEX term_hierarchy_parent_idx ON term_hierarchy(parent);

--
-- Table structure for term_node
--

CREATE TABLE term_node (
  nid integer NOT NULL default '0',
  tid integer NOT NULL default '0'
);
CREATE INDEX term_node_nid_idx ON term_node(nid);
CREATE INDEX term_node_tid_idx ON term_node(tid);

--
-- Table structure for term_relation
--

CREATE TABLE term_relation (
  tid1 integer NOT NULL default '0',
  tid2 integer NOT NULL default '0'
);
CREATE INDEX term_relation_tid1_idx ON term_relation(tid1);
CREATE INDEX term_relation_tid2_idx ON term_relation(tid2);

--
-- Table structure for term_synonym
--

CREATE TABLE term_synonym (
  tid integer NOT NULL default '0',
  name varchar(255) NOT NULL default ''
);
CREATE INDEX term_synonym_tid_idx ON term_synonym(tid);
CREATE INDEX term_synonym_name_idx ON term_synonym(name);

--
-- Table structure for users
--

CREATE TABLE users (
  uid SERIAL,
  name varchar(60) NOT NULL default '',
  pass varchar(32) NOT NULL default '',
  mail varchar(64) default '',
  homepage varchar(128) NOT NULL default '',
  mode smallint NOT NULL default '0',
  sort smallint default '0',
  threshold smallint default '0',
  theme varchar(255) NOT NULL default '',
  signature varchar(255) NOT NULL default '',
  timestamp integer NOT NULL default '0',
  hostname varchar(128) NOT NULL default '',
  status smallint NOT NULL default '0',
  timezone varchar(8) default NULL,
  rating decimal(8,2) default NULL,
  language char(2) NOT NULL default '',
  sid varchar(32) NOT NULL default '',
  init varchar(64) default '',
  session text default '',
  data text default '',
  rid integer NOT NULL default '0',
  PRIMARY KEY  (uid),
  UNIQUE (name)
);
CREATE INDEX users_sid_idx ON users(sid);
CREATE INDEX users_timestamp_idx ON users(timestamp);

--
-- Table structure for variable
--

CREATE TABLE variable (
  name varchar(32) NOT NULL default '',
  value text NOT NULL default '',
  PRIMARY KEY  (name)
);

--
-- Table structure for vocabulary
--

CREATE TABLE vocabulary (
  vid SERIAL,
  name varchar(255) NOT NULL default '',
  description text default '',
  relations smallint NOT NULL default '0',
  hierarchy smallint NOT NULL default '0',
  multiple smallint NOT NULL default '0',
  required smallint NOT NULL default '0',
  types text default '',
  weight smallint NOT NULL default '0',
  PRIMARY KEY  (vid)
);

--
-- Table structure for watchdog
--

CREATE TABLE watchdog (
  wid SERIAL,
  uid integer NOT NULL default '0',
  type varchar(16) NOT NULL default '',
  message text NOT NULL default '',
  location varchar(128) NOT NULL default '',
  hostname varchar(128) NOT NULL default '',
  timestamp integer NOT NULL default '0',
  PRIMARY KEY  (wid)
);

--
-- Insert some default values
--

INSERT INTO system VALUES ('modules/block.module','block','module','',1);
INSERT INTO system VALUES ('modules/comment.module','comment','module','',1);
INSERT INTO system VALUES ('modules/help.module','help','module','',1);
INSERT INTO system VALUES ('modules/node.module','node','module','',1);
INSERT INTO system VALUES ('modules/page.module','page','module','',1);
INSERT INTO system VALUES ('modules/story.module','story','module','',1);
INSERT INTO system VALUES ('modules/taxonomy.module','taxonomy','module','',1);
INSERT INTO system VALUES ('themes/marvin/marvin.theme','marvin','theme','Internet explorer, Netscape, Opera',1);

INSERT INTO variable(name,value) VALUES('update_start', '2002-05-15');
INSERT INTO variable(name,value) VALUES('theme_default','s:6:"marvin";');

INSERT INTO blocks(module,delta,status) VALUES('user', '0', '1');
INSERT INTO blocks(module,delta,status) VALUES('user', '1', '1');

---
--- Functions
---

CREATE FUNCTION "greatest"(integer, integer) RETURNS integer AS '
BEGIN
  IF $1 > $2 THEN
    RETURN $1;
  END IF;
  RETURN $2;
END;
' LANGUAGE 'plpgsql';

