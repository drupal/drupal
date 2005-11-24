--
-- Table structure for access
--

CREATE TABLE access (
  aid SERIAL,
  mask varchar(255) NOT NULL default '',
  type varchar(255) NOT NULL default '',
  status smallint NOT NULL default '0',
  PRIMARY KEY (aid)
);

--
-- Table structure for accesslog
--

CREATE TABLE accesslog (
  aid SERIAL,
  mask varchar(255) NOT NULL default '',
  title varchar(255) default NULL,
  path varchar(255) default NULL,
  url varchar(255) default NULL,
  hostname varchar(128) default NULL,
  uid integer default '0',
  timestamp integer NOT NULL default '0',
  PRIMARY KEY (aid)
);
CREATE INDEX accesslog_timestamp_idx ON accesslog (timestamp);
--
-- Table structure for table 'aggregator_category'
--

CREATE TABLE aggregator_category (
  cid serial,
  title varchar(255) NOT NULL default '',
  description text,
  block smallint NOT NULL default '0',
  PRIMARY KEY  (cid),
  UNIQUE (title)
);

--
-- Table structure for table 'aggregator_category_feed'
--

CREATE TABLE aggregator_category_feed (
  fid integer NOT NULL default '0',
  cid integer NOT NULL default '0',
  PRIMARY KEY  (fid,cid)
);

--
-- Table structure for table 'aggregator_category_item'
--

CREATE TABLE aggregator_category_item (
  iid integer NOT NULL default '0',
  cid integer NOT NULL default '0',
  PRIMARY KEY  (iid,cid)
);

--
-- Table structure for table 'aggregator_feed'
--

CREATE TABLE aggregator_feed (
  fid serial,
  title varchar(255) NOT NULL default '',
  url varchar(255) NOT NULL default '',
  refresh integer NOT NULL default '0',
  checked integer NOT NULL default '0',
  link varchar(255) NOT NULL default '',
  description text,
  image text,
  etag varchar(255) NOT NULL default '',
  modified integer NOT NULL default '0',
  block smallint NOT NULL default '0',
  PRIMARY KEY  (fid),
  UNIQUE (url),
  UNIQUE (title)
);

--
-- Table structure for table 'aggregator_item'
--

CREATE TABLE aggregator_item (
  iid SERIAL,
  fid integer NOT NULL default '0',
  title varchar(255) NOT NULL default '',
  link varchar(255) NOT NULL default '',
  author varchar(255) NOT NULL default '',
  description text,
  timestamp integer default NULL,
  PRIMARY KEY  (iid)
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
  delta varchar(32) NOT NULL default '0',
  status smallint NOT NULL default '0',
  weight smallint NOT NULL default '0',
  region smallint NOT NULL default '0',
  custom smallint NOT NULL default '0',
  throttle smallint NOT NULL default '0',
  visibility smallint NOT NULL default '0',
  pages text NOT NULL default '',
  types text NOT NULL default ''
);

--
-- Table structure for book
--

CREATE TABLE book (
  nid integer NOT NULL default '0',
  parent integer NOT NULL default '0',
  weight smallint NOT NULL default '0',
  log text default '',
  PRIMARY KEY (nid)
);
CREATE INDEX book_nid_idx ON book(nid);
CREATE INDEX book_parent ON book(parent);

--
-- Table structure for boxes
--

CREATE TABLE boxes (
  bid SERIAL,
  title varchar(64) NOT NULL default '',
  body text default '',
  info varchar(128) NOT NULL default '',
  format smallint NOT NULL default '0',
  PRIMARY KEY  (bid),
  UNIQUE (info),
  UNIQUE (title)
);

--
-- Table structure for cache
--

CREATE TABLE cache (
  cid varchar(255) NOT NULL default '',
  data text default '',
  expire integer NOT NULL default '0',
  created integer NOT NULL default '0',
  headers text default '',
  PRIMARY KEY  (cid)
);
CREATE INDEX cache_expire_idx ON cache(expire);

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
  score integer NOT NULL default '0',
  status smallint  NOT NULL default '0',
  format smallint NOT NULL default '0',
  thread varchar(255) default '',
  users text default '',
  name varchar(60) default NULL,
  mail varchar(64) default NULL,
  homepage varchar(255) default NULL,
  PRIMARY KEY  (cid)
);
CREATE INDEX comments_nid_idx ON comments(nid);

--
-- Table structre for table 'node_last_comment'
--

CREATE TABLE node_comment_statistics (
  nid integer NOT NULL,
  last_comment_timestamp integer NOT NULL default '0',
  last_comment_name varchar(60)  default NULL,
  last_comment_uid integer NOT NULL default '0',
  comment_count integer NOT NULL default '0',
  PRIMARY KEY (nid)
);
CREATE INDEX node_comment_statistics_timestamp_idx ON node_comment_statistics(last_comment_timestamp);

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
-- Table structure for table 'files'
--

CREATE TABLE files (
  fid SERIAL,
  nid integer NOT NULL default '0',
  filename varchar(255) NOT NULL default '',
  filepath varchar(255) NOT NULL default '',
  filemime varchar(255) NOT NULL default '',
  filesize integer NOT NULL default '0',
  list smallint NOT NULL default '0',
  PRIMARY KEY  (fid)
);

--
-- Table structure for table 'filter_formats'
--

CREATE TABLE filter_formats (
  format SERIAL,
  name varchar(255) NOT NULL default '',
  roles varchar(255) NOT NULL default '',
  cache smallint NOT NULL default '0',
  PRIMARY KEY (format)
);

--
-- Table structure for table 'filters'
--

CREATE TABLE filters (
  format integer NOT NULL DEFAULT '0',
  module varchar(64) NOT NULL DEFAULT '',
  delta smallint NOT NULL DEFAULT 1,
  weight smallint DEFAULT '0' NOT NULL
);
CREATE INDEX filters_module_idx ON filters(module);
CREATE INDEX filters_weight_idx ON filters(weight);

--
-- Table structure for table 'flood'
--

CREATE TABLE flood (
  event varchar(64) NOT NULL default '',
  hostname varchar(128) NOT NULL default '',
  timestamp integer NOT NULL default '0'
);

--
-- Table structure for table 'forum'
--

CREATE TABLE forum (
  nid integer NOT NULL default '0',
  tid integer NOT NULL default '0',
  shadow integer NOT NULL default '0',
  PRIMARY KEY  (nid)
);
CREATE INDEX forum_tid_idx ON forum(tid);

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
-- Table structure for locales_meta
--

CREATE TABLE locales_meta (
  locale varchar(12) NOT NULL default '',
  name varchar(64) NOT NULL default '',
  enabled int4 NOT NULL default '0',
  isdefault int4 NOT NULL default '0',
  plurals int4 NOT NULL default '0',
  formula varchar(128) NOT NULL default '',
  PRIMARY KEY  (locale)
);

--
-- Table structure for locales_source
--


CREATE TABLE locales_source (
  lid SERIAL,
  location varchar(255) NOT NULL default '',
  source text NOT NULL,
  PRIMARY KEY  (lid)
);

--
-- Table structure for locales_target
--

CREATE TABLE locales_target (
  lid int4 NOT NULL default '0',
  translation text DEFAULT '' NOT NULL,
  locale varchar(12) NOT NULL default '',
  plid int4 NOT NULL default '0',
  plural int4 NOT NULL default '0',
  UNIQUE  (lid)
);
CREATE INDEX locales_target_lid_idx ON locales_target(lid);
CREATE INDEX locales_target_lang_idx ON locales_target(locale);
CREATE INDEX locales_target_plid_idx ON locales_target(plid);
CREATE INDEX locales_target_plural_idx ON locales_target(plural);

--
-- Table structure for table 'menu'
--


CREATE TABLE menu (
  mid serial,
  pid integer NOT NULL default '0',
  path varchar(255) NOT NULL default '',
  title varchar(255) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  weight smallint NOT NULL default '0',
  type smallint NOT NULL default '0',
  PRIMARY KEY  (mid)
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
  uid integer NOT NULL default '0',
  status integer NOT NULL default '1',
  created integer NOT NULL default '0',
  changed integer NOT NULL default '0',
  comment integer NOT NULL default '0',
  promote integer NOT NULL default '0',
  moderate integer NOT NULL default '0',
  teaser text NOT NULL default '',
  body text NOT NULL default '',
  revisions text NOT NULL default '',
  sticky integer NOT NULL default '0',
  format smallint NOT NULL default '0',
  PRIMARY KEY  (nid)
);
CREATE INDEX node_type_idx ON node(type);
CREATE INDEX node_title_idx ON node(title,type);
CREATE INDEX node_status_idx ON node(status);
CREATE INDEX node_uid_idx ON node(uid);
CREATE INDEX node_moderate_idx ON node (moderate);
CREATE INDEX node_promote_status_idx ON node (promote, status);
CREATE INDEX node_created ON node(created);
CREATE INDEX node_changed ON node(changed);

--
-- Table structure for table `node_access`
--

CREATE TABLE node_access (
  nid SERIAL,
  gid integer NOT NULL default '0',
  realm varchar(255) NOT NULL default '',
  grant_view smallint NOT NULL default '0',
  grant_update smallint NOT NULL default '0',
  grant_delete smallint NOT NULL default '0',
  PRIMARY KEY  (nid,gid,realm)
);


--
-- Table structure for table 'node_counter'
--

CREATE TABLE node_counter (
  nid integer NOT NULL default '0',
  totalcount integer NOT NULL default '0',
  daycount integer NOT NULL default '0',
  timestamp integer NOT NULL default '0',
  PRIMARY KEY  (nid)
);
CREATE INDEX node_counter_totalcount_idx ON node_counter(totalcount);
CREATE INDEX node_counter_daycount_idx ON node_counter(daycount);
CREATE INDEX node_counter_timestamp_idx ON node_counter(timestamp);

--
-- Table structure for table 'url_alias'
--

CREATE TABLE profile_fields (
  fid serial,
  title varchar(255) default NULL,
  name varchar(128) default NULL,
  explanation TEXT default NULL,
  category varchar(255) default NULL,
  page varchar(255) default NULL,
  type varchar(128) default NULL,
  weight smallint DEFAULT '0' NOT NULL,
  required smallint DEFAULT '0' NOT NULL,
  register smallint DEFAULT '0' NOT NULL,
  visibility smallint DEFAULT '0' NOT NULL,
  options text,
  UNIQUE (name),
  PRIMARY KEY (fid)
);
CREATE INDEX profile_fields_category ON profile_fields (category);

--
-- Table structure for table 'profile_values'
--

CREATE TABLE profile_values (
  fid integer default '0',
  uid integer default '0',
  value text
);
CREATE INDEX profile_values_uid ON profile_values (uid);
CREATE INDEX profile_values_fid ON profile_values (fid);

CREATE TABLE url_alias (
  pid serial,
  src varchar(128) NOT NULL default '',
  dst varchar(128) NOT NULL default '',
  PRIMARY KEY  (pid)
);
CREATE INDEX url_alias_dst_idx ON url_alias(dst);
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
  polled text NOT NULL default '',
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
-- Table structure for queue
--

CREATE TABLE queue (
  nid integer NOT NULL default '0',
  uid integer NOT NULL default '0',
  vote integer NOT NULL default '0',
  PRIMARY KEY (nid, uid)
);
CREATE INDEX queue_nid_idx ON queue(nid);
CREATE INDEX queue_uid_idx ON queue(uid);

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
  sid integer NOT NULL default '0',
  type varchar(16) default NULL,
  fromsid integer NOT NULL default '0',
  fromtype varchar(16) default NULL,
  score integer default NULL
);
CREATE INDEX search_index_sid_idx ON search_index(sid);
CREATE INDEX search_index_fromsid_idx ON search_index(fromsid);
CREATE INDEX search_index_word_idx ON search_index(word);

--
-- Table structures for search_total
--

CREATE TABLE search_total (
  word varchar(50) NOT NULL default '',
  count float default NULL
);
CREATE INDEX search_total_word_idx ON search_total(word);

--
-- Table structure for sessions
--

CREATE TABLE sessions (
  uid integer not null,
  sid varchar(32) NOT NULL default '',
  hostname varchar(128) NOT NULL default '',
  timestamp integer NOT NULL default '0',
  session text,
  PRIMARY KEY (sid)
);

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
-- Table structure for system
--

CREATE TABLE system (
  filename varchar(255) NOT NULL default '',
  name varchar(255) NOT NULL default '',
  type varchar(255) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  status integer NOT NULL default '0',
  throttle smallint NOT NULL default '0',
  bootstrap integer NOT NULL default '0',
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
  tid integer NOT NULL default '0',
  PRIMARY KEY (tid,nid)
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
  uid integer NOT NULL default '0',
  name varchar(60) NOT NULL default '',
  pass varchar(32) NOT NULL default '',
  mail varchar(64) default '',
  mode smallint NOT NULL default '0',
  sort smallint default '0',
  threshold smallint default '0',
  theme varchar(255) NOT NULL default '',
  signature varchar(255) NOT NULL default '',
  created integer NOT NULL default '0',
  changed integer NOT NULL default '0',
  status smallint NOT NULL default '0',
  timezone varchar(8) default NULL,
  language varchar(12) NOT NULL default '',
  picture varchar(255) NOT NULL DEFAULT '',
  init varchar(64) default '',
  data text default '',
  PRIMARY KEY  (uid),
  UNIQUE (name)
);
CREATE INDEX users_changed_idx ON users(changed);

CREATE SEQUENCE users_uid_seq INCREMENT 1 START 1;

--
-- Table structure for users_roles
--

CREATE TABLE users_roles (
  uid integer NOT NULL default '0',
  rid integer NOT NULL default '0',
  PRIMARY KEY (uid, rid)
);

--
-- Table structure for variable
--

CREATE TABLE variable (
  name varchar(48) NOT NULL default '',
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
  help varchar(255) NOT NULL default '',
  relations smallint NOT NULL default '0',
  hierarchy smallint NOT NULL default '0',
  multiple smallint NOT NULL default '0',
  required smallint NOT NULL default '0',
  module varchar(255) NOT NULL default '',
  weight smallint NOT NULL default '0',
  PRIMARY KEY  (vid)
);

--
-- Table structure for vocabulary_node_types
--

CREATE TABLE vocabulary_node_types (
  vid integer NOT NULL default '0',
  type varchar(16) NOT NULL default '',
  PRIMARY KEY (vid, type)
);

--
-- Table structure for watchdog
--

CREATE TABLE watchdog (
  wid SERIAL,
  uid integer NOT NULL default '0',
  type varchar(16) NOT NULL default '',
  message text NOT NULL default '',
  severity smallint NOT NULL default '0',
  link varchar(255) NOT NULL default '',
  location varchar(128) NOT NULL default '',
  hostname varchar(128) NOT NULL default '',
  timestamp integer NOT NULL default '0',
  PRIMARY KEY  (wid)
);

--
-- Insert some default values
--

INSERT INTO system VALUES ('modules/block.module','block','module','',1,0,0);
INSERT INTO system VALUES ('modules/comment.module','comment','module','',1,0,0);
INSERT INTO system VALUES ('modules/filter.module','filter','module','',1,0,0);
INSERT INTO system VALUES ('modules/help.module','help','module','',1,0,0);
INSERT INTO system VALUES ('modules/node.module','node','module','',1,0,0);
INSERT INTO system VALUES ('modules/page.module','page','module','',1,0,0);
INSERT INTO system VALUES ('modules/story.module','story','module','',1,0,0);
INSERT INTO system VALUES ('modules/system.module','system','module','',1,0,0);
INSERT INTO system VALUES ('modules/taxonomy.module','taxonomy','module','',1,0,0);
INSERT INTO system VALUES ('modules/user.module','user','module','',1,0,0);
INSERT INTO system VALUES ('modules/watchdog.module','watchdog','module','',1,0,0);
INSERT INTO system VALUES ('themes/bluemarine/xtemplate.xtmpl','bluemarine','theme','themes/engines/xtemplate/xtemplate.engine',1,0,0);
INSERT INTO system VALUES ('themes/engines/xtemplate/xtemplate.engine','xtemplate','theme_engine','',1,0,0);

INSERT INTO variable(name,value) VALUES('update_start', 's:10:"2005-03-21";');
INSERT INTO variable(name,value) VALUES('theme_default','s:10:"bluemarine";');
INSERT INTO users(uid,name,mail) VALUES(0,'','');
INSERT INTO users_roles(uid,rid) VALUES(0, 1);

INSERT INTO role (name) VALUES ('anonymous user');
INSERT INTO permission VALUES (1,'access content',0);

INSERT INTO role (name) VALUES ('authenticated user');
INSERT INTO permission VALUES (2,'access comments, access content, post comments, post comments without approval',0);

INSERT INTO blocks(module,delta,status) VALUES('user', 0, 1);
INSERT INTO blocks(module,delta,status) VALUES('user', 1, 1);

INSERT INTO node_access VALUES (0, 0, 'all', 1, 0, 0);

INSERT INTO filter_formats (name, roles, cache) VALUES ('Filtered HTML',',1,2,',1);
INSERT INTO filter_formats (name, roles, cache) VALUES ('PHP code','',0);
INSERT INTO filter_formats (name, roles, cache) VALUES ('Full HTML','',1);
INSERT INTO filters VALUES (1,'filter',0,0);
INSERT INTO filters VALUES (1,'filter',2,1);
INSERT INTO filters VALUES (2,'filter',1,0);
INSERT INTO filters VALUES (3,'filter',2,0);
INSERT INTO variable (name,value) VALUES ('filter_html_1','i:1;');

INSERT INTO locales_meta(locale, name, enabled, isdefault) VALUES('en', 'English', '1', '1');

---
--- Alter some sequences
---
ALTER SEQUENCE menu_mid_seq RESTART 2;


---
--- Functions
---

CREATE FUNCTION "greatest"(integer, integer) RETURNS integer AS '
BEGIN
  IF $2 IS NULL THEN
    RETURN $1;
  END IF;
  IF $1 > $2 THEN
    RETURN $1;
  END IF;
  RETURN $2;
END;
' LANGUAGE 'plpgsql';

CREATE FUNCTION "greatest"(integer, integer, integer) RETURNS integer AS '
  SELECT greatest($1, greatest($2, $3));
' LANGUAGE 'sql';


CREATE FUNCTION "rand"() RETURNS float AS '
BEGIN
  RETURN random();
END;
' LANGUAGE 'plpgsql';

CREATE FUNCTION "concat"(text, text) RETURNS text AS '
BEGIN
  RETURN $1 || $2;
END;
' LANGUAGE 'plpgsql';

CREATE FUNCTION "if"(boolean, anyelement, anyelement) RETURNS anyelement AS '
  SELECT CASE WHEN $1 THEN $2 ELSE $3 END;
' LANGUAGE 'sql';

