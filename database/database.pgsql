--
-- Selected TOC Entries:
--
\connect - root
--
-- TOC Entry ID 1 (OID 0)
--
-- Name: drupal Type: DATABASE Owner: root
--

Create Database "drupal";

\connect drupal postgres
\connect - postgres
--
-- TOC Entry ID 92 (OID 18720)
--
-- Name: "plpgsql_call_handler" () Type: FUNCTION Owner: postgres
--

CREATE FUNCTION "plpgsql_call_handler" () RETURNS opaque AS '/usr/lib/postgresql/lib/plpgsql.so', 'plpgsql_call_handler' LANGUAGE 'C';

--
-- TOC Entry ID 93 (OID 18721)
--
-- Name: plpgsql Type: PROCEDURAL LANGUAGE Owner:
--

CREATE TRUSTED PROCEDURAL LANGUAGE 'plpgsql' HANDLER "plpgsql_call_handler" LANCOMPILER 'PL/pgSQL';

\connect - root
--
-- TOC Entry ID 27 (OID 46073)
--
-- Name: pga_queries Type: TABLE Owner: root
--

CREATE TABLE "pga_queries" (
  "queryname" character varying(64),
  "querytype" character(1),
  "querycommand" text,
  "querytables" text,
  "querylinks" text,
  "queryresults" text,
  "querycomments" text
);

--
-- TOC Entry ID 28 (OID 46104)
--
-- Name: pga_forms Type: TABLE Owner: root
--

CREATE TABLE "pga_forms" (
  "formname" character varying(64),
  "formsource" text
);

--
-- TOC Entry ID 29 (OID 46130)
--
-- Name: pga_scripts Type: TABLE Owner: root
--

CREATE TABLE "pga_scripts" (
  "scriptname" character varying(64),
  "scriptsource" text
);

--
-- TOC Entry ID 30 (OID 46156)
--
-- Name: pga_reports Type: TABLE Owner: root
--

CREATE TABLE "pga_reports" (
  "reportname" character varying(64),
  "reportsource" text,
  "reportbody" text,
  "reportprocs" text,
  "reportoptions" text
);

--
-- TOC Entry ID 31 (OID 46185)
--
-- Name: pga_schema Type: TABLE Owner: root
--

CREATE TABLE "pga_schema" (
  "schemaname" character varying(64),
  "schematables" text,
  "schemalinks" text
);

--
-- TOC Entry ID 2 (OID 46212)
--
-- Name: access_aid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "access_aid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 32 (OID 46231)
--
-- Name: access Type: TABLE Owner: root
--

CREATE TABLE "access" (
  "aid" integer DEFAULT nextval('"access_aid_seq"'::text) NOT NULL,
  "mask" character varying(255) NOT NULL,
  "type" character varying(255) NOT NULL,
  "status" smallint NOT NULL,
  Constraint "access_pkey" Primary Key ("aid")
);

--
-- TOC Entry ID 3 (OID 46317)
--
-- Name: boxes_bid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "boxes_bid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 33 (OID 46336)
--
-- Name: boxes Type: TABLE Owner: root
--

CREATE TABLE "boxes" (
  "bid" integer DEFAULT nextval('"boxes_bid_seq"'::text) NOT NULL,
  "title" character varying(64) NOT NULL,
  "body" text NOT NULL,
  "info" character varying(128) NOT NULL,
  "type" smallint NOT NULL,
  Constraint "boxes_pkey" Primary Key ("bid")
);

--
-- TOC Entry ID 4 (OID 46369)
--
-- Name: bundle_bid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "bundle_bid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 34 (OID 46388)
--
-- Name: bundle Type: TABLE Owner: root
--

CREATE TABLE "bundle" (
  "bid" integer DEFAULT nextval('"bundle_bid_seq"'::text) NOT NULL,
  "title" character varying(255) NOT NULL,
  "attributes" character varying(255) NOT NULL,
  Constraint "bundle_pkey" Primary Key ("bid")
);

--
-- TOC Entry ID 35 (OID 46404)
--
-- Name: cache Type: TABLE Owner: root
--

CREATE TABLE "cache" (
  "url" character varying(255) NOT NULL,
  "data" text NOT NULL,
  "timestamp" integer NOT NULL,
  Constraint "cache_pkey" Primary Key ("url")
);

--
-- TOC Entry ID 5 (OID 46434)
--
-- Name: category_cid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "category_cid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 36 (OID 46453)
--
-- Name: category Type: TABLE Owner: root
--

CREATE TABLE "category" (
  "cid" integer DEFAULT nextval('"category_cid_seq"'::text) NOT NULL,
  "name" character varying(32) NOT NULL,
  "type" character varying(16) NOT NULL,
  "post" integer NOT NULL,
  "dump" integer NOT NULL,
  "expire" integer NOT NULL,
  "comment" integer NOT NULL,
  "submission" integer NOT NULL,
  "promote" integer NOT NULL,
  Constraint "category_pkey" Primary Key ("cid")
);

--
-- TOC Entry ID 6 (OID 46475)
--
-- Name: channel_id_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "channel_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 37 (OID 46494)
--
-- Name: channel Type: TABLE Owner: root
--

CREATE TABLE "channel" (
  "id" integer DEFAULT nextval('"channel_id_seq"'::text) NOT NULL,
  "site" character varying(255) NOT NULL,
  "file" character varying(255) NOT NULL,
  "url" character varying(255) NOT NULL,
  "contact" character varying(255) NOT NULL,
  "timestamp" integer NOT NULL,
  Constraint "channel_pkey" Primary Key ("id")
);

--
-- TOC Entry ID 7 (OID 46513)
--
-- Name: chatevents_id_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "chatevents_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 38 (OID 46532)
--
-- Name: chatevents Type: TABLE Owner: root
--

CREATE TABLE "chatevents" (
  "id" integer DEFAULT nextval('"chatevents_id_seq"'::text) NOT NULL,
  "body" character varying(255) NOT NULL,
  "timestamp" integer NOT NULL,
  Constraint "chatevents_pkey" Primary Key ("id")
);

--
-- TOC Entry ID 8 (OID 46548)
--
-- Name: chatmembers_id_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "chatmembers_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 39 (OID 46567)
--
-- Name: chatmembers Type: TABLE Owner: root
--

CREATE TABLE "chatmembers" (
  "id" integer DEFAULT nextval('"chatmembers_id_seq"'::text) NOT NULL,
  "nick" character varying(32) NOT NULL,
  "timestamp" integer NOT NULL,
  Constraint "chatmembers_pkey" Primary Key ("id")
);

--
-- TOC Entry ID 9 (OID 46583)
--
-- Name: collection_cid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "collection_cid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 40 (OID 46602)
--
-- Name: collection Type: TABLE Owner: root
--

CREATE TABLE "collection" (
  "cid" integer DEFAULT nextval('"collection_cid_seq"'::text) NOT NULL,
  "name" character varying(32) NOT NULL,
  "types" character varying(128) NOT NULL,
  Constraint "collection_pkey" Primary Key ("cid")
);

--
-- TOC Entry ID 10 (OID 46618)
--
-- Name: comments_cid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "comments_cid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 41 (OID 46637)
--
-- Name: cvs Type: TABLE Owner: root
--

CREATE TABLE "cvs" (
  "username" character varying(32) NOT NULL,
  "files" text NOT NULL,
  "status" smallint NOT NULL,
  "message" text NOT NULL,
  "timestamp" integer NOT NULL
);

--
-- TOC Entry ID 42 (OID 46695)
--
-- Name: pga_layout Type: TABLE Owner: root
--

CREATE TABLE "pga_layout" (
  "tablename" character varying(64),
  "nrcols" smallint,
  "colnames" text,
  "colwidth" text
);

--
-- TOC Entry ID 11 (OID 46773)
--
-- Name: entry_eid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "entry_eid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 43 (OID 46792)
--
-- Name: entry Type: TABLE Owner: root
--

CREATE TABLE "entry" (
  "eid" integer DEFAULT nextval('"entry_eid_seq"'::text) NOT NULL,
  "name" character varying NOT NULL,
  "attributes" character varying(32) NOT NULL,
  "collection" character varying(32),
  Constraint "entry_pkey" Primary Key ("eid")
);

--
-- TOC Entry ID 12 (OID 46824)
--
-- Name: feed_fid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "feed_fid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 44 (OID 46843)
--
-- Name: feed Type: TABLE Owner: root
--

CREATE TABLE "feed" (
  "fid" integer DEFAULT nextval('"feed_fid_seq"'::text) NOT NULL,
  "title" character varying(255) NOT NULL,
  "url" character varying(255) NOT NULL,
  "refresh" integer NOT NULL,
  "uncache" integer NOT NULL,
  "timestamp" integer NOT NULL,
  "attributes" character varying(255) NOT NULL,
  "link" character varying(255) NOT NULL,
  "description" text NOT NULL,
  Constraint "feed_pkey" Primary Key ("fid")
);

--
-- TOC Entry ID 13 (OID 46880)
--
-- Name: file_lid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "file_lid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 45 (OID 46899)
--
-- Name: file Type: TABLE Owner: root
--

CREATE TABLE "file" (
  "lid" integer DEFAULT nextval('"file_lid_seq"'::text) NOT NULL,
  "nid" integer NOT NULL,
  "version" character varying(10) NOT NULL,
  "url" character varying(255) NOT NULL,
  "downloads" integer NOT NULL,
  "abstract" text NOT NULL,
  "description" text NOT NULL,
  "homepage" character varying(255) NOT NULL,
  Constraint "file_pkey" Primary Key ("lid")
);

--
-- TOC Entry ID 14 (OID 46935)
--
-- Name: forum_lid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "forum_lid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 15 (OID 46985)
--
-- Name: item_iid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "item_iid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 46 (OID 47004)
--
-- Name: item Type: TABLE Owner: root
--

CREATE TABLE "item" (
  "iid" integer DEFAULT nextval('"item_iid_seq"'::text) NOT NULL,
  "fid" integer NOT NULL,
  "title" character varying(255) NOT NULL,
  "link" character varying(255) NOT NULL,
  "author" character varying(255) NOT NULL,
  "description" text NOT NULL,
  "timestamp" integer NOT NULL,
  "attributes" character varying(255) NOT NULL,
  Constraint "item_pkey" Primary Key ("iid")
);

--
-- TOC Entry ID 47 (OID 47040)
--
-- Name: layout Type: TABLE Owner: root
--

CREATE TABLE "layout" (
  "userid" integer NOT NULL,
  "block" character varying(64) NOT NULL
);

--
-- TOC Entry ID 16 (OID 47051)
--
-- Name: locales_id_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "locales_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 48 (OID 47070)
--
-- Name: locales Type: TABLE Owner: root
--

CREATE TABLE "locales" (
  "id" integer DEFAULT nextval('"locales_id_seq"'::text) NOT NULL,
  "location" character varying(128) NOT NULL,
  "string" text NOT NULL,
  "da" text NOT NULL,
  "fi" text NOT NULL,
  "fr" text NOT NULL,
  "en" text NOT NULL,
  "es" text NOT NULL,
  "nl" text NOT NULL,
  "no" text NOT NULL,
  "sw" text NOT NULL,
  Constraint "locales_pkey" Primary Key ("id")
);

--
-- TOC Entry ID 49 (OID 47109)
--
-- Name: modules Type: TABLE Owner: root
--

CREATE TABLE "modules" (
  "name" character varying(64) NOT NULL,
  Constraint "modules_pkey" Primary Key ("name")
);

--
-- TOC Entry ID 17 (OID 47122)
--
-- Name: node_nid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "node_nid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 50 (OID 47141)
--
-- Name: notify Type: TABLE Owner: root
--

CREATE TABLE "notify" (
  "uid" integer NOT NULL,
  "status" integer NOT NULL,
  "node" integer NOT NULL,
  "comment" integer NOT NULL,
  "attempts" integer NOT NULL,
  Constraint "notify_pkey" Primary Key ("uid")
);

--
-- TOC Entry ID 18 (OID 47211)
--
-- Name: poll_lid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "poll_lid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 51 (OID 47230)
--
-- Name: poll Type: TABLE Owner: root
--

CREATE TABLE "poll" (
  "lid" integer DEFAULT nextval('"poll_lid_seq"'::text) NOT NULL,
  "nid" integer NOT NULL,
  "runtime" integer NOT NULL,
  "voters" text NOT NULL,
  "active" integer NOT NULL,
  Constraint "poll_pkey" Primary Key ("lid")
);

--
-- TOC Entry ID 19 (OID 47263)
--
-- Name: poll_choices_chid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "poll_choices_chid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 52 (OID 47282)
--
-- Name: poll_choices Type: TABLE Owner: root
--

CREATE TABLE "poll_choices" (
  "chid" integer DEFAULT nextval('"poll_choices_chid_seq"'::text) NOT NULL,
  "nid" integer NOT NULL,
  "chtext" character varying(128) DEFAULT '' NOT NULL,
  "chvotes" integer NOT NULL,
  "chorder" integer NOT NULL,
  Constraint "poll_choices_pkey" Primary Key ("chid")
);

--
-- TOC Entry ID 53 (OID 47301)
--
-- Name: rating Type: TABLE Owner: root
--

CREATE TABLE "rating" (
  "userid" integer NOT NULL,
  "new" integer NOT NULL,
  "old" integer NOT NULL,
  Constraint "user_pkey" Primary Key ("userid")
);

--
-- TOC Entry ID 54 (OID 47316)
--
-- Name: referer Type: TABLE Owner: root
--

CREATE TABLE "referer" (
  "url" character varying(255) DEFAULT '' NOT NULL,
  "timestamp" integer NOT NULL
);

--
-- TOC Entry ID 20 (OID 47328)
--
-- Name: role_rid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "role_rid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 21 (OID 47347)
--
-- Name: site_sid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "site_sid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 55 (OID 47366)
--
-- Name: site Type: TABLE Owner: root
--

CREATE TABLE "site" (
  "sid" integer DEFAULT nextval('"site_sid_seq"'::text) NOT NULL,
  "name" character varying(128) DEFAULT '' NOT NULL,
  "link" character varying(255) DEFAULT '' NOT NULL,
  "size" text NOT NULL,
  "timestamp" integer NOT NULL,
  "feed" character varying(255) DEFAULT '' NOT NULL,
  Constraint "site_pkey" Primary Key ("sid")
);

--
-- TOC Entry ID 22 (OID 47454)
--
-- Name: tag_tid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "tag_tid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 56 (OID 47473)
--
-- Name: tag Type: TABLE Owner: root
--

CREATE TABLE "tag" (
  "tid" integer DEFAULT nextval('"tag_tid_seq"'::text) NOT NULL,
  "name" character varying(32) DEFAULT '' NOT NULL,
  "attributes" character varying(255) DEFAULT '' NOT NULL,
  "collections" character varying(32) DEFAULT '' NOT NULL,
  Constraint "tag_pkey" Primary Key ("tid")
);

--
-- TOC Entry ID 23 (OID 47493)
--
-- Name: topic_tid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "topic_tid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 57 (OID 47512)
--
-- Name: topic Type: TABLE Owner: root
--

CREATE TABLE "topic" (
  "tid" integer DEFAULT nextval('"topic_tid_seq"'::text) NOT NULL,
  "pid" integer NOT NULL,
  "name" character varying(32) DEFAULT '' NOT NULL,
  "moderate" text NOT NULL,
  Constraint "topic_pkey" Primary Key ("tid")
);

--
-- TOC Entry ID 24 (OID 47545)
--
-- Name: trip_link_lid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "trip_link_lid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 58 (OID 47564)
--
-- Name: trip_link Type: TABLE Owner: root
--

CREATE TABLE "trip_link" (
  "lid" integer DEFAULT nextval('"trip_link_lid_seq"'::text) NOT NULL,
  "nid" integer NOT NULL,
  "url" text NOT NULL,
  "body" text NOT NULL,
  Constraint "trip_link_pkey" Primary Key ("lid")
);

--
-- TOC Entry ID 25 (OID 47596)
--
-- Name: users_uid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "users_uid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 59 (OID 47615)
--
-- Name: variable Type: TABLE Owner: root
--

CREATE TABLE "variable" (
  "name" character varying(32) DEFAULT '' NOT NULL,
  "value" text NOT NULL,
  Constraint "variable_pkey" Primary Key ("name")
);

--
-- TOC Entry ID 26 (OID 47645)
--
-- Name: watchdog_id_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "watchdog_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 60 (OID 47664)
--
-- Name: watchdog Type: TABLE Owner: root
--

CREATE TABLE "watchdog" (
  "id" integer DEFAULT nextval('"watchdog_id_seq"'::text) NOT NULL,
  "uid" integer NOT NULL,
  "type" character varying(16) DEFAULT '' NOT NULL,
  "message" character varying(255) DEFAULT '' NOT NULL,
  "location" character varying(128) DEFAULT '' NOT NULL,
  "hostname" character varying(128) DEFAULT '' NOT NULL,
  "timestamp" integer NOT NULL,
  Constraint "watchdog_pkey" Primary Key ("id")
);

--
-- TOC Entry ID 61 (OID 47688)
--
-- Name: users Type: TABLE Owner: root
--

CREATE TABLE "users" (
  "uid" integer DEFAULT nextval('"users_uid_seq"'::text) NOT NULL,
  "name" character varying(60) DEFAULT '' NOT NULL,
  "mail" character varying(64) DEFAULT '',
  "homepage" character varying(128) DEFAULT '',
  "mode" integer,
  "sort" integer,
  "threshold" integer,
  "theme" character varying(255) DEFAULT '',
  "signature" character varying(255) DEFAULT '',
  "last_access" integer,
  "last_host" character varying(255),
  "status" integer NOT NULL,
  "timezone" character varying(8),
  "rating" numeric(8,2),
  "language" character(2) DEFAULT '',
  "role" character varying(32) DEFAULT '' NOT NULL,
  "session" character varying(128) DEFAULT '',
  "hostname" character varying(128) DEFAULT '',
  "timestamp" integer NOT NULL,
  "jabber" character varying(128) DEFAULT '',
  "drupal" character varying(128) DEFAULT '',
  "init" character varying(64) DEFAULT '',
  "real_email" character varying(64) DEFAULT '',
  "pass" character varying(32) DEFAULT '',
  Constraint "users_pkey" Primary Key ("uid")
);

--
-- TOC Entry ID 62 (OID 47739)
--
-- Name: blocks Type: TABLE Owner: root
--

CREATE TABLE "blocks" (
  "name" character varying(64) NOT NULL,
  "module" character varying(64) NOT NULL,
  "delta" smallint NOT NULL,
  "status" smallint,
  "weight" smallint,
  "region" smallint,
  "remove" smallint,
  Constraint "blocks_pkey" Primary Key ("name")
);

--
-- TOC Entry ID 63 (OID 47758)
--
-- Name: role Type: TABLE Owner: root
--

CREATE TABLE "role" (
  "rid" integer DEFAULT nextval('"role_rid_seq"'::text) NOT NULL,
  "name" character varying(32) DEFAULT '' NOT NULL,
  "perm" text,
  Constraint "role_pkey" Primary Key ("rid")
);

--
-- TOC Entry ID 64 (OID 47790)
--
-- Name: node Type: TABLE Owner: root
--

CREATE TABLE "node" (
  "nid" integer DEFAULT nextval('"node_nid_seq"'::text) NOT NULL,
  "lid" integer NOT NULL,
  "type" character varying(16) NOT NULL,
  "score" integer NOT NULL,
  "votes" integer NOT NULL,
  "uid" integer NOT NULL,
  "status" integer NOT NULL,
  "timestamp" integer NOT NULL,
  "comment" smallint NOT NULL,
  "promote" smallint NOT NULL,
  "moderate" text NOT NULL,
  "users" text,
  "timestamp_posted" integer,
  "timestamp_queued" integer,
  "timestamp_hidden" integer,
  "attributes" character varying(255),
  "title" character varying(128),
  Constraint "node_pkey" Primary Key ("nid")
);

--
-- TOC Entry ID 65 (OID 47871)
--
-- Name: moderate Type: TABLE Owner: root
--

CREATE TABLE "moderate" (
  "cid" integer,
  "nid" integer,
  "uid" integer NOT NULL,
  "score" smallint NOT NULL,
  "timestamp" integer NOT NULL
);

--
-- TOC Entry ID 66 (OID 47885)
--
-- Name: comments Type: TABLE Owner: root
--

CREATE TABLE "comments" (
  "cid" integer DEFAULT nextval('"comments_cid_seq"'::text) NOT NULL,
  "pid" integer NOT NULL,
  "lid" integer NOT NULL,
  "uid" integer NOT NULL,
  "subject" character varying(64) NOT NULL,
  "comment" text NOT NULL,
  "hostname" character varying(128) NOT NULL,
  "timestamp" integer NOT NULL,
  "link" character varying(16) NOT NULL,
  Constraint "comments_pkey" Primary Key ("cid")
);

--
-- TOC Entry ID 67 (OID 48008)
--
-- Name: blog Type: TABLE Owner: root
--

CREATE TABLE "blog" (
  "nid" integer NOT NULL,
  "body" text NOT NULL
);

--
-- TOC Entry ID 68 (OID 48061)
--
-- Name: story Type: TABLE Owner: root
--

CREATE TABLE "story" (
  "nid" integer NOT NULL,
  "abstract" text NOT NULL,
  "body" text NOT NULL
);

--
-- TOC Entry ID 69 (OID 48116)
--
-- Name: page Type: TABLE Owner: root
--

CREATE TABLE "page" (
  "nid" integer NOT NULL,
  "link" character varying(128) DEFAULT '' NOT NULL,
  "body" text NOT NULL,
  "format" integer NOT NULL
);

--
-- TOC Entry ID 70 (OID 48171)
--
-- Name: forum Type: TABLE Owner: root
--

CREATE TABLE "forum" (
  "nid" integer NOT NULL,
  "body" text NOT NULL
);

--
-- TOC Entry ID 71 (OID 48228)
--
-- Name: book Type: TABLE Owner: root
--

CREATE TABLE "book" (
  "nid" integer NOT NULL,
  "body" text NOT NULL,
  "section" integer,
  "parent" integer NOT NULL,
  "weight" integer NOT NULL,
  "pid" integer NOT NULL,
  "log" text NOT NULL
);

--
-- TOC Entry ID 72 (OID 46231)
--
-- Name: "mask_access_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "mask_access_ukey" on "access" using btree ( "mask" "varchar_ops" );

--
-- TOC Entry ID 73 (OID 46336)
--
-- Name: "title_boxes_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "title_boxes_ukey" on "boxes" using btree ( "title" "varchar_ops" );

--
-- TOC Entry ID 74 (OID 46336)
--
-- Name: "info_boxes_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "info_boxes_ukey" on "boxes" using btree ( "info" "varchar_ops" );

--
-- TOC Entry ID 75 (OID 46453)
--
-- Name: "name_category_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "name_category_ukey" on "category" using btree ( "name" "varchar_ops" );

--
-- TOC Entry ID 76 (OID 46494)
--
-- Name: "url_channel_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "url_channel_ukey" on "channel" using btree ( "url" "varchar_ops" );

--
-- TOC Entry ID 77 (OID 46494)
--
-- Name: "file_channel_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "file_channel_ukey" on "channel" using btree ( "file" "varchar_ops" );

--
-- TOC Entry ID 78 (OID 46494)
--
-- Name: "site_channel_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "site_channel_ukey" on "channel" using btree ( "site" "varchar_ops" );

--
-- TOC Entry ID 79 (OID 46602)
--
-- Name: "name_collection_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "name_collection_ukey" on "collection" using btree ( "name" "varchar_ops" );

--
-- TOC Entry ID 80 (OID 46792)
--
-- Name: "entry_namcoll_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "entry_namcoll_ukey" on "entry" using btree ( "name" "varchar_ops", "collection" "varchar_ops" );

--
-- TOC Entry ID 81 (OID 46843)
--
-- Name: "url_feed_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "url_feed_ukey" on "feed" using btree ( "url" "varchar_ops" );

--
-- TOC Entry ID 82 (OID 46843)
--
-- Name: "title_feed_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "title_feed_ukey" on "feed" using btree ( "title" "varchar_ops" );

--
-- TOC Entry ID 83 (OID 47366)
--
-- Name: "link_site_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "link_site_ukey" on "site" using btree ( "link" "varchar_ops" );

--
-- TOC Entry ID 84 (OID 47366)
--
-- Name: "name_site_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "name_site_ukey" on "site" using btree ( "name" "varchar_ops" );

--
-- TOC Entry ID 85 (OID 47473)
--
-- Name: "tag_namecoll_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "tag_namecoll_ukey" on "tag" using btree ( "name" "varchar_ops", "collections" "varchar_ops" );

--
-- TOC Entry ID 86 (OID 47512)
--
-- Name: "name_topic_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "name_topic_ukey" on "topic" using btree ( "name" "varchar_ops" );

--
-- TOC Entry ID 91 (OID 47790)
--
-- Name: "uid_node_key" Type: INDEX Owner: root
--

CREATE  INDEX "uid_node_key" on "node" using btree ( "uid" "int4_ops" );

--
-- TOC Entry ID 87 (OID 47871)
--
-- Name: "moderate_cid_key" Type: INDEX Owner: root
--

CREATE  INDEX "moderate_cid_key" on "moderate" using btree ( "cid" "int4_ops" );

--
-- TOC Entry ID 88 (OID 47871)
--
-- Name: "moderate_nid_key" Type: INDEX Owner: root
--

CREATE  INDEX "moderate_nid_key" on "moderate" using btree ( "nid" "int4_ops" );

--
-- TOC Entry ID 89 (OID 47885)
--
-- Name: "lid_comments_key" Type: INDEX Owner: root
--

CREATE  INDEX "lid_comments_key" on "comments" using btree ( "lid" "int4_ops" );

--
-- TOC Entry ID 90 (OID 47885)
--
-- Name: "uid_comments_key" Type: INDEX Owner: root
--

CREATE  INDEX "uid_comments_key" on "comments" using btree ( "uid" "int4_ops" );

