--
-- Selected TOC Entries:
--
--- \connect - postgres
--
-- TOC Entry ID 174 (OID 18720)
--
-- Name: "plpgsql_call_handler" () Type: FUNCTION Owner: postgres
--

--- CREATE FUNCTION "plpgsql_call_handler" () RETURNS opaque AS '/usr/lib/postgresql/lib/plpgsql.so', 'plpgsql_call_handler' LANGUAGE 'C';

--
-- TOC Entry ID 175 (OID 18721)
--
-- Name: plpgsql Type: PROCEDURAL LANGUAGE Owner:
--

--- CREATE TRUSTED PROCEDURAL LANGUAGE 'plpgsql' HANDLER "plpgsql_call_handler" LANCOMPILER 'PL/pgSQL';

\connect - root
--
-- TOC Entry ID 62 (OID 20075)
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
-- TOC Entry ID 63 (OID 20075)
--
-- Name: pga_queries Type: ACL Owner:
--

REVOKE ALL on "pga_queries" from PUBLIC;
GRANT ALL on "pga_queries" to PUBLIC;
GRANT ALL on "pga_queries" to "root";

--
-- TOC Entry ID 64 (OID 20106)
--
-- Name: pga_forms Type: TABLE Owner: root
--

CREATE TABLE "pga_forms" (
  "formname" character varying(64),
  "formsource" text
);

--
-- TOC Entry ID 65 (OID 20106)
--
-- Name: pga_forms Type: ACL Owner:
--

REVOKE ALL on "pga_forms" from PUBLIC;
GRANT ALL on "pga_forms" to PUBLIC;
GRANT ALL on "pga_forms" to "root";

--
-- TOC Entry ID 66 (OID 20132)
--
-- Name: pga_scripts Type: TABLE Owner: root
--

CREATE TABLE "pga_scripts" (
  "scriptname" character varying(64),
  "scriptsource" text
);

--
-- TOC Entry ID 67 (OID 20132)
--
-- Name: pga_scripts Type: ACL Owner:
--

REVOKE ALL on "pga_scripts" from PUBLIC;
GRANT ALL on "pga_scripts" to PUBLIC;
GRANT ALL on "pga_scripts" to "root";

--
-- TOC Entry ID 68 (OID 20158)
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
-- TOC Entry ID 69 (OID 20158)
--
-- Name: pga_reports Type: ACL Owner:
--

REVOKE ALL on "pga_reports" from PUBLIC;
GRANT ALL on "pga_reports" to PUBLIC;
GRANT ALL on "pga_reports" to "root";

--
-- TOC Entry ID 70 (OID 20187)
--
-- Name: pga_schema Type: TABLE Owner: root
--

CREATE TABLE "pga_schema" (
  "schemaname" character varying(64),
  "schematables" text,
  "schemalinks" text
);

--
-- TOC Entry ID 71 (OID 20187)
--
-- Name: pga_schema Type: ACL Owner:
--

REVOKE ALL on "pga_schema" from PUBLIC;
GRANT ALL on "pga_schema" to PUBLIC;
GRANT ALL on "pga_schema" to "root";

--
-- TOC Entry ID 2 (OID 20214)
--
-- Name: access_aid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "access_aid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 3 (OID 20214)
--
-- Name: access_aid_seq Type: ACL Owner:
--

REVOKE ALL on "access_aid_seq" from PUBLIC;
GRANT ALL on "access_aid_seq" to "root";
GRANT ALL on "access_aid_seq" to "wallaby";

--
-- TOC Entry ID 72 (OID 20233)
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
-- TOC Entry ID 73 (OID 20233)
--
-- Name: access Type: ACL Owner:
--

REVOKE ALL on "access" from PUBLIC;
GRANT ALL on "access" to "root";
GRANT ALL on "access" to "wallaby";

--
-- TOC Entry ID 4 (OID 20269)
--
-- Name: blog_lid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "blog_lid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 5 (OID 20269)
--
-- Name: blog_lid_seq Type: ACL Owner:
--

REVOKE ALL on "blog_lid_seq" from PUBLIC;
GRANT ALL on "blog_lid_seq" to "root";
GRANT ALL on "blog_lid_seq" to "wallaby";

--
-- TOC Entry ID 74 (OID 20288)
--
-- Name: blog Type: TABLE Owner: root
--

CREATE TABLE "blog" (
  "lid" integer DEFAULT nextval('"blog_lid_seq"'::text) NOT NULL,
  "nid" integer NOT NULL,
  "body" text NOT NULL,
  Constraint "blog_pkey" Primary Key ("lid")
);

--
-- TOC Entry ID 75 (OID 20288)
--
-- Name: blog Type: ACL Owner:
--

REVOKE ALL on "blog" from PUBLIC;
GRANT ALL on "blog" to "root";
GRANT ALL on "blog" to "wallaby";

--
-- TOC Entry ID 6 (OID 20319)
--
-- Name: book_lid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "book_lid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 7 (OID 20319)
--
-- Name: book_lid_seq Type: ACL Owner:
--

REVOKE ALL on "book_lid_seq" from PUBLIC;
GRANT ALL on "book_lid_seq" to "root";
GRANT ALL on "book_lid_seq" to "wallaby";

--
-- TOC Entry ID 8 (OID 20374)
--
-- Name: boxes_bid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "boxes_bid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 9 (OID 20374)
--
-- Name: boxes_bid_seq Type: ACL Owner:
--

REVOKE ALL on "boxes_bid_seq" from PUBLIC;
GRANT ALL on "boxes_bid_seq" to "root";
GRANT ALL on "boxes_bid_seq" to "wallaby";

--
-- TOC Entry ID 76 (OID 20393)
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
-- TOC Entry ID 77 (OID 20393)
--
-- Name: boxes Type: ACL Owner:
--

REVOKE ALL on "boxes" from PUBLIC;
GRANT ALL on "boxes" to "root";
GRANT ALL on "boxes" to "wallaby";

--
-- TOC Entry ID 10 (OID 20435)
--
-- Name: bundle_bid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "bundle_bid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 11 (OID 20435)
--
-- Name: bundle_bid_seq Type: ACL Owner:
--

REVOKE ALL on "bundle_bid_seq" from PUBLIC;
GRANT ALL on "bundle_bid_seq" to "root";
GRANT ALL on "bundle_bid_seq" to "wallaby";

--
-- TOC Entry ID 78 (OID 20454)
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
-- TOC Entry ID 79 (OID 20454)
--
-- Name: bundle Type: ACL Owner:
--

REVOKE ALL on "bundle" from PUBLIC;
GRANT ALL on "bundle" to "root";
GRANT ALL on "bundle" to "wallaby";

--
-- TOC Entry ID 80 (OID 20470)
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
-- TOC Entry ID 81 (OID 20470)
--
-- Name: cache Type: ACL Owner:
--

REVOKE ALL on "cache" from PUBLIC;
GRANT ALL on "cache" to "root";
GRANT ALL on "cache" to "wallaby";

--
-- TOC Entry ID 12 (OID 20500)
--
-- Name: category_cid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "category_cid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 13 (OID 20500)
--
-- Name: category_cid_seq Type: ACL Owner:
--

REVOKE ALL on "category_cid_seq" from PUBLIC;
GRANT ALL on "category_cid_seq" to "root";
GRANT ALL on "category_cid_seq" to "wallaby";

--
-- TOC Entry ID 82 (OID 20519)
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
-- TOC Entry ID 83 (OID 20519)
--
-- Name: category Type: ACL Owner:
--

REVOKE ALL on "category" from PUBLIC;
GRANT ALL on "category" to "root";
GRANT ALL on "category" to "wallaby";

--
-- TOC Entry ID 14 (OID 20544)
--
-- Name: channel_id_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "channel_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 15 (OID 20544)
--
-- Name: channel_id_seq Type: ACL Owner:
--

REVOKE ALL on "channel_id_seq" from PUBLIC;
GRANT ALL on "channel_id_seq" to "root";
GRANT ALL on "channel_id_seq" to "wallaby";

--
-- TOC Entry ID 84 (OID 20563)
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
-- TOC Entry ID 85 (OID 20563)
--
-- Name: channel Type: ACL Owner:
--

REVOKE ALL on "channel" from PUBLIC;
GRANT ALL on "channel" to "root";
GRANT ALL on "channel" to "wallaby";

--
-- TOC Entry ID 16 (OID 20591)
--
-- Name: chatevents_id_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "chatevents_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 17 (OID 20591)
--
-- Name: chatevents_id_seq Type: ACL Owner:
--

REVOKE ALL on "chatevents_id_seq" from PUBLIC;
GRANT ALL on "chatevents_id_seq" to "root";
GRANT ALL on "chatevents_id_seq" to "wallaby";

--
-- TOC Entry ID 86 (OID 20610)
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
-- TOC Entry ID 87 (OID 20610)
--
-- Name: chatevents Type: ACL Owner:
--

REVOKE ALL on "chatevents" from PUBLIC;
GRANT ALL on "chatevents" to "root";
GRANT ALL on "chatevents" to "wallaby";

--
-- TOC Entry ID 18 (OID 20626)
--
-- Name: chatmembers_id_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "chatmembers_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 19 (OID 20626)
--
-- Name: chatmembers_id_seq Type: ACL Owner:
--

REVOKE ALL on "chatmembers_id_seq" from PUBLIC;
GRANT ALL on "chatmembers_id_seq" to "root";
GRANT ALL on "chatmembers_id_seq" to "wallaby";

--
-- TOC Entry ID 88 (OID 20645)
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
-- TOC Entry ID 89 (OID 20645)
--
-- Name: chatmembers Type: ACL Owner:
--

REVOKE ALL on "chatmembers" from PUBLIC;
GRANT ALL on "chatmembers" to "root";
GRANT ALL on "chatmembers" to "wallaby";

--
-- TOC Entry ID 20 (OID 20680)
--
-- Name: collection_cid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "collection_cid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 21 (OID 20680)
--
-- Name: collection_cid_seq Type: ACL Owner:
--

REVOKE ALL on "collection_cid_seq" from PUBLIC;
GRANT ALL on "collection_cid_seq" to "root";
GRANT ALL on "collection_cid_seq" to "wallaby";

--
-- TOC Entry ID 90 (OID 20699)
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
-- TOC Entry ID 91 (OID 20699)
--
-- Name: collection Type: ACL Owner:
--

REVOKE ALL on "collection" from PUBLIC;
GRANT ALL on "collection" to "root";
GRANT ALL on "collection" to "wallaby";

--
-- TOC Entry ID 22 (OID 20718)
--
-- Name: comments_cid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "comments_cid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 23 (OID 20718)
--
-- Name: comments_cid_seq Type: ACL Owner:
--

REVOKE ALL on "comments_cid_seq" from PUBLIC;
GRANT ALL on "comments_cid_seq" to "root";
GRANT ALL on "comments_cid_seq" to "wallaby";

--
-- TOC Entry ID 92 (OID 20780)
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
-- TOC Entry ID 93 (OID 20780)
--
-- Name: cvs Type: ACL Owner:
--

REVOKE ALL on "cvs" from PUBLIC;
GRANT ALL on "cvs" to "root";
GRANT ALL on "cvs" to "wallaby";

--
-- TOC Entry ID 94 (OID 20828)
--
-- Name: diaries Type: TABLE Owner: root
--

CREATE TABLE "diaries" (
  "id" integer DEFAULT nextval('"diary_id_seq"'::text) NOT NULL,
  "author" integer NOT NULL,
  "text" text NOT NULL,
  "timestamp" text NOT NULL
);

--
-- TOC Entry ID 95 (OID 20828)
--
-- Name: diaries Type: ACL Owner:
--

REVOKE ALL on "diaries" from PUBLIC;
GRANT ALL on "diaries" to "root";
GRANT ALL on "diaries" to "wallaby";

--
-- TOC Entry ID 96 (OID 20860)
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
-- TOC Entry ID 97 (OID 20860)
--
-- Name: pga_layout Type: ACL Owner:
--

REVOKE ALL on "pga_layout" from PUBLIC;
GRANT ALL on "pga_layout" to PUBLIC;
GRANT ALL on "pga_layout" to "root";

--
-- TOC Entry ID 24 (OID 21124)
--
-- Name: diary_lid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "diary_lid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 25 (OID 21124)
--
-- Name: diary_lid_seq Type: ACL Owner:
--

REVOKE ALL on "diary_lid_seq" from PUBLIC;
GRANT ALL on "diary_lid_seq" to "root";
GRANT ALL on "diary_lid_seq" to "wallaby";

--
-- TOC Entry ID 98 (OID 21143)
--
-- Name: diary Type: TABLE Owner: root
--

CREATE TABLE "diary" (
  "lid" integer DEFAULT nextval('"diary_lid_seq"'::text) NOT NULL,
  "nid" integer NOT NULL,
  "body" text NOT NULL,
  Constraint "diary_pkey" Primary Key ("lid")
);

--
-- TOC Entry ID 99 (OID 21143)
--
-- Name: diary Type: ACL Owner:
--

REVOKE ALL on "diary" from PUBLIC;
GRANT ALL on "diary" to "root";
GRANT ALL on "diary" to "wallaby";

--
-- TOC Entry ID 26 (OID 21174)
--
-- Name: entry_eid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "entry_eid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 27 (OID 21174)
--
-- Name: entry_eid_seq Type: ACL Owner:
--

REVOKE ALL on "entry_eid_seq" from PUBLIC;
GRANT ALL on "entry_eid_seq" to "root";
GRANT ALL on "entry_eid_seq" to "wallaby";

--
-- TOC Entry ID 100 (OID 21193)
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
-- TOC Entry ID 101 (OID 21193)
--
-- Name: entry Type: ACL Owner:
--

REVOKE ALL on "entry" from PUBLIC;
GRANT ALL on "entry" to "root";
GRANT ALL on "entry" to "wallaby";

--
-- TOC Entry ID 28 (OID 21225)
--
-- Name: feed_fid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "feed_fid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 29 (OID 21225)
--
-- Name: feed_fid_seq Type: ACL Owner:
--

REVOKE ALL on "feed_fid_seq" from PUBLIC;
GRANT ALL on "feed_fid_seq" to "root";
GRANT ALL on "feed_fid_seq" to "wallaby";

--
-- TOC Entry ID 102 (OID 21244)
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
-- TOC Entry ID 103 (OID 21244)
--
-- Name: feed Type: ACL Owner:
--

REVOKE ALL on "feed" from PUBLIC;
GRANT ALL on "feed" to "root";
GRANT ALL on "feed" to "wallaby";

--
-- TOC Entry ID 30 (OID 21287)
--
-- Name: file_lid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "file_lid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 31 (OID 21287)
--
-- Name: file_lid_seq Type: ACL Owner:
--

REVOKE ALL on "file_lid_seq" from PUBLIC;
GRANT ALL on "file_lid_seq" to "root";
GRANT ALL on "file_lid_seq" to "wallaby";

--
-- TOC Entry ID 104 (OID 21306)
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
-- TOC Entry ID 105 (OID 21306)
--
-- Name: file Type: ACL Owner:
--

REVOKE ALL on "file" from PUBLIC;
GRANT ALL on "file" to "root";
GRANT ALL on "file" to "wallaby";

--
-- TOC Entry ID 32 (OID 21342)
--
-- Name: forum_lid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "forum_lid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 33 (OID 21342)
--
-- Name: forum_lid_seq Type: ACL Owner:
--

REVOKE ALL on "forum_lid_seq" from PUBLIC;
GRANT ALL on "forum_lid_seq" to "root";
GRANT ALL on "forum_lid_seq" to "wallaby";

--
-- TOC Entry ID 106 (OID 21361)
--
-- Name: forum Type: TABLE Owner: root
--

CREATE TABLE "forum" (
  "lid" integer DEFAULT nextval('"forum_lid_seq"'::text) NOT NULL,
  "nid" integer NOT NULL,
  "body" text NOT NULL,
  Constraint "forum_pkey" Primary Key ("lid")
);

--
-- TOC Entry ID 107 (OID 21361)
--
-- Name: forum Type: ACL Owner:
--

REVOKE ALL on "forum" from PUBLIC;
GRANT ALL on "forum" to "root";
GRANT ALL on "forum" to "wallaby";

--
-- TOC Entry ID 34 (OID 21392)
--
-- Name: item_iid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "item_iid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 35 (OID 21392)
--
-- Name: item_iid_seq Type: ACL Owner:
--

REVOKE ALL on "item_iid_seq" from PUBLIC;
GRANT ALL on "item_iid_seq" to "root";
GRANT ALL on "item_iid_seq" to "wallaby";

--
-- TOC Entry ID 108 (OID 21411)
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
-- TOC Entry ID 109 (OID 21411)
--
-- Name: item Type: ACL Owner:
--

REVOKE ALL on "item" from PUBLIC;
GRANT ALL on "item" to "root";
GRANT ALL on "item" to "wallaby";

--
-- TOC Entry ID 110 (OID 21447)
--
-- Name: layout Type: TABLE Owner: root
--

CREATE TABLE "layout" (
  "userid" integer NOT NULL,
  "block" character varying(64) NOT NULL
);

--
-- TOC Entry ID 111 (OID 21447)
--
-- Name: layout Type: ACL Owner:
--

REVOKE ALL on "layout" from PUBLIC;
GRANT ALL on "layout" to "root";
GRANT ALL on "layout" to "wallaby";

--
-- TOC Entry ID 36 (OID 21458)
--
-- Name: locales_id_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "locales_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 37 (OID 21458)
--
-- Name: locales_id_seq Type: ACL Owner:
--

REVOKE ALL on "locales_id_seq" from PUBLIC;
GRANT ALL on "locales_id_seq" to "root";
GRANT ALL on "locales_id_seq" to "wallaby";

--
-- TOC Entry ID 112 (OID 21477)
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
-- TOC Entry ID 113 (OID 21477)
--
-- Name: locales Type: ACL Owner:
--

REVOKE ALL on "locales" from PUBLIC;
GRANT ALL on "locales" to "root";
GRANT ALL on "locales" to "wallaby";

--
-- TOC Entry ID 114 (OID 21516)
--
-- Name: modules Type: TABLE Owner: root
--

CREATE TABLE "modules" (
  "name" character varying(64) NOT NULL,
  Constraint "modules_pkey" Primary Key ("name")
);

--
-- TOC Entry ID 115 (OID 21516)
--
-- Name: modules Type: ACL Owner:
--

REVOKE ALL on "modules" from PUBLIC;
GRANT ALL on "modules" to "root";
GRANT ALL on "modules" to "wallaby";

--
-- TOC Entry ID 38 (OID 21529)
--
-- Name: node_nid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "node_nid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 39 (OID 21529)
--
-- Name: node_nid_seq Type: ACL Owner:
--

REVOKE ALL on "node_nid_seq" from PUBLIC;
GRANT ALL on "node_nid_seq" to "root";
GRANT ALL on "node_nid_seq" to "wallaby";

--
-- TOC Entry ID 116 (OID 22296)
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
-- TOC Entry ID 117 (OID 22296)
--
-- Name: notify Type: ACL Owner:
--

REVOKE ALL on "notify" from PUBLIC;
GRANT ALL on "notify" to "root";
GRANT ALL on "notify" to "wallaby";

--
-- TOC Entry ID 40 (OID 22313)
--
-- Name: page_lid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "page_lid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 41 (OID 22313)
--
-- Name: page_lid_seq Type: ACL Owner:
--

REVOKE ALL on "page_lid_seq" from PUBLIC;
GRANT ALL on "page_lid_seq" to "root";
GRANT ALL on "page_lid_seq" to "wallaby";

--
-- TOC Entry ID 118 (OID 22332)
--
-- Name: page Type: TABLE Owner: root
--

CREATE TABLE "page" (
  "lid" integer DEFAULT nextval('"page_lid_seq"'::text) NOT NULL,
  "nid" integer NOT NULL,
  "link" character varying(128) DEFAULT '' NOT NULL,
  "body" text NOT NULL,
  "format" integer NOT NULL,
  Constraint "page_pkey" Primary Key ("lid")
);

--
-- TOC Entry ID 119 (OID 22332)
--
-- Name: page Type: ACL Owner:
--

REVOKE ALL on "page" from PUBLIC;
GRANT ALL on "page" to "root";
GRANT ALL on "page" to "wallaby";

--
-- TOC Entry ID 42 (OID 22366)
--
-- Name: poll_lid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "poll_lid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 43 (OID 22366)
--
-- Name: poll_lid_seq Type: ACL Owner:
--

REVOKE ALL on "poll_lid_seq" from PUBLIC;
GRANT ALL on "poll_lid_seq" to "root";
GRANT ALL on "poll_lid_seq" to "wallaby";

--
-- TOC Entry ID 120 (OID 22385)
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
-- TOC Entry ID 121 (OID 22385)
--
-- Name: poll Type: ACL Owner:
--

REVOKE ALL on "poll" from PUBLIC;
GRANT ALL on "poll" to "root";
GRANT ALL on "poll" to "wallaby";

--
-- TOC Entry ID 44 (OID 22418)
--
-- Name: poll_choices_chid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "poll_choices_chid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 45 (OID 22418)
--
-- Name: poll_choices_chid_seq Type: ACL Owner:
--

REVOKE ALL on "poll_choices_chid_seq" from PUBLIC;
GRANT ALL on "poll_choices_chid_seq" to "root";
GRANT ALL on "poll_choices_chid_seq" to "wallaby";

--
-- TOC Entry ID 122 (OID 22437)
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
-- TOC Entry ID 123 (OID 22437)
--
-- Name: poll_choices Type: ACL Owner:
--

REVOKE ALL on "poll_choices" from PUBLIC;
GRANT ALL on "poll_choices" to "root";
GRANT ALL on "poll_choices" to "wallaby";

--
-- TOC Entry ID 124 (OID 22456)
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
-- TOC Entry ID 125 (OID 22456)
--
-- Name: rating Type: ACL Owner:
--

REVOKE ALL on "rating" from PUBLIC;
GRANT ALL on "rating" to "root";
GRANT ALL on "rating" to "wallaby";

--
-- TOC Entry ID 126 (OID 22471)
--
-- Name: referer Type: TABLE Owner: root
--

CREATE TABLE "referer" (
  "url" character varying(255) DEFAULT '' NOT NULL,
  "timestamp" integer NOT NULL
);

--
-- TOC Entry ID 127 (OID 22471)
--
-- Name: referer Type: ACL Owner:
--

REVOKE ALL on "referer" from PUBLIC;
GRANT ALL on "referer" to "root";
GRANT ALL on "referer" to "wallaby";

--
-- TOC Entry ID 46 (OID 22483)
--
-- Name: role_rid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "role_rid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 47 (OID 22483)
--
-- Name: role_rid_seq Type: ACL Owner:
--

REVOKE ALL on "role_rid_seq" from PUBLIC;
GRANT ALL on "role_rid_seq" to "root";
GRANT ALL on "role_rid_seq" to "wallaby";

--
-- TOC Entry ID 48 (OID 22534)
--
-- Name: site_sid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "site_sid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 49 (OID 22534)
--
-- Name: site_sid_seq Type: ACL Owner:
--

REVOKE ALL on "site_sid_seq" from PUBLIC;
GRANT ALL on "site_sid_seq" to "root";
GRANT ALL on "site_sid_seq" to "wallaby";

--
-- TOC Entry ID 128 (OID 22553)
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
-- TOC Entry ID 129 (OID 22553)
--
-- Name: site Type: ACL Owner:
--

REVOKE ALL on "site" from PUBLIC;
GRANT ALL on "site" to "root";
GRANT ALL on "site" to "wallaby";

--
-- TOC Entry ID 50 (OID 22590)
--
-- Name: story_lid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "story_lid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 51 (OID 22590)
--
-- Name: story_lid_seq Type: ACL Owner:
--

REVOKE ALL on "story_lid_seq" from PUBLIC;
GRANT ALL on "story_lid_seq" to "root";
GRANT ALL on "story_lid_seq" to "wallaby";

--
-- TOC Entry ID 130 (OID 22609)
--
-- Name: story Type: TABLE Owner: root
--

CREATE TABLE "story" (
  "lid" integer DEFAULT nextval('"story_lid_seq"'::text) NOT NULL,
  "nid" integer NOT NULL,
  "abstract" text NOT NULL,
  "body" text NOT NULL,
  Constraint "story_pkey" Primary Key ("lid")
);

--
-- TOC Entry ID 131 (OID 22609)
--
-- Name: story Type: ACL Owner:
--

REVOKE ALL on "story" from PUBLIC;
GRANT ALL on "story" to "root";
GRANT ALL on "story" to "wallaby";

--
-- TOC Entry ID 52 (OID 22641)
--
-- Name: tag_tid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "tag_tid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 53 (OID 22641)
--
-- Name: tag_tid_seq Type: ACL Owner:
--

REVOKE ALL on "tag_tid_seq" from PUBLIC;
GRANT ALL on "tag_tid_seq" to "root";
GRANT ALL on "tag_tid_seq" to "wallaby";

--
-- TOC Entry ID 132 (OID 22660)
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
-- TOC Entry ID 133 (OID 22660)
--
-- Name: tag Type: ACL Owner:
--

REVOKE ALL on "tag" from PUBLIC;
GRANT ALL on "tag" to "root";
GRANT ALL on "tag" to "wallaby";

--
-- TOC Entry ID 54 (OID 22680)
--
-- Name: topic_tid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "topic_tid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 55 (OID 22680)
--
-- Name: topic_tid_seq Type: ACL Owner:
--

REVOKE ALL on "topic_tid_seq" from PUBLIC;
GRANT ALL on "topic_tid_seq" to "root";
GRANT ALL on "topic_tid_seq" to "wallaby";

--
-- TOC Entry ID 134 (OID 22699)
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
-- TOC Entry ID 135 (OID 22699)
--
-- Name: topic Type: ACL Owner:
--

REVOKE ALL on "topic" from PUBLIC;
GRANT ALL on "topic" to "root";
GRANT ALL on "topic" to "wallaby";

--
-- TOC Entry ID 56 (OID 22732)
--
-- Name: trip_link_lid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "trip_link_lid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 57 (OID 22732)
--
-- Name: trip_link_lid_seq Type: ACL Owner:
--

REVOKE ALL on "trip_link_lid_seq" from PUBLIC;
GRANT ALL on "trip_link_lid_seq" to "root";
GRANT ALL on "trip_link_lid_seq" to "wallaby";

--
-- TOC Entry ID 136 (OID 22751)
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
-- TOC Entry ID 137 (OID 22751)
--
-- Name: trip_link Type: ACL Owner:
--

REVOKE ALL on "trip_link" from PUBLIC;
GRANT ALL on "trip_link" to "root";
GRANT ALL on "trip_link" to "wallaby";

--
-- TOC Entry ID 58 (OID 22783)
--
-- Name: users_uid_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "users_uid_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 59 (OID 22783)
--
-- Name: users_uid_seq Type: ACL Owner:
--

REVOKE ALL on "users_uid_seq" from PUBLIC;
GRANT ALL on "users_uid_seq" to "root";
GRANT ALL on "users_uid_seq" to "wallaby";

--
-- TOC Entry ID 138 (OID 22855)
--
-- Name: variable Type: TABLE Owner: root
--

CREATE TABLE "variable" (
  "name" character varying(32) DEFAULT '' NOT NULL,
  "value" text NOT NULL,
  Constraint "variable_pkey" Primary Key ("name")
);

--
-- TOC Entry ID 139 (OID 22855)
--
-- Name: variable Type: ACL Owner:
--

REVOKE ALL on "variable" from PUBLIC;
GRANT ALL on "variable" to "root";
GRANT ALL on "variable" to "wallaby";

--
-- TOC Entry ID 60 (OID 22885)
--
-- Name: watchdog_id_seq Type: SEQUENCE Owner: root
--

CREATE SEQUENCE "watchdog_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;

--
-- TOC Entry ID 61 (OID 22885)
--
-- Name: watchdog_id_seq Type: ACL Owner:
--

REVOKE ALL on "watchdog_id_seq" from PUBLIC;
GRANT ALL on "watchdog_id_seq" to "root";
GRANT ALL on "watchdog_id_seq" to "wallaby";

--
-- TOC Entry ID 140 (OID 22904)
--
-- Name: watchdog Type: TABLE Owner: root
--

CREATE TABLE "watchdog" (
  "id" integer DEFAULT nextval('"watchdog_id_seq"'::text) NOT NULL,
  "userid" integer NOT NULL,
  "type" character varying(16) DEFAULT '' NOT NULL,
  "message" character varying(255) DEFAULT '' NOT NULL,
  "location" character varying(128) DEFAULT '' NOT NULL,
  "hostname" character varying(128) DEFAULT '' NOT NULL,
  "timestamp" integer NOT NULL,
  Constraint "watchdog_pkey" Primary Key ("id")
);

--
-- TOC Entry ID 141 (OID 22904)
--
-- Name: watchdog Type: ACL Owner:
--

REVOKE ALL on "watchdog" from PUBLIC;
GRANT ALL on "watchdog" to "root";
GRANT ALL on "watchdog" to "wallaby";

--
-- TOC Entry ID 142 (OID 23291)
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
-- TOC Entry ID 143 (OID 23291)
--
-- Name: users Type: ACL Owner:
--

REVOKE ALL on "users" from PUBLIC;
GRANT ALL on "users" to "root";
GRANT ALL on "users" to "wallaby";

--
-- TOC Entry ID 144 (OID 23453)
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
-- TOC Entry ID 145 (OID 23453)
--
-- Name: blocks Type: ACL Owner:
--

REVOKE ALL on "blocks" from PUBLIC;
GRANT ALL on "blocks" to "root";
GRANT ALL on "blocks" to "wallaby";

--
-- TOC Entry ID 146 (OID 23732)
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
-- TOC Entry ID 147 (OID 23732)
--
-- Name: role Type: ACL Owner:
--

REVOKE ALL on "role" from PUBLIC;
GRANT ALL on "role" to "root";
GRANT ALL on "role" to "wallaby";

--
-- TOC Entry ID 148 (OID 23996)
--
-- Name: node Type: TABLE Owner: root
--

CREATE TABLE "node" (
  "nid" integer DEFAULT nextval('"node_nid_seq"'::text) NOT NULL,
  "lid" integer NOT NULL,
  "type" character varying(16) NOT NULL,
  "score" integer NOT NULL,
  "votes" integer NOT NULL,
  "author" integer NOT NULL,
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
-- TOC Entry ID 149 (OID 23996)
--
-- Name: node Type: ACL Owner:
--

REVOKE ALL on "node" from PUBLIC;
GRANT ALL on "node" to "root";
GRANT ALL on "node" to "wallaby";

--
-- TOC Entry ID 150 (OID 24087)
--
-- Name: book Type: TABLE Owner: root
--

CREATE TABLE "book" (
  "lid" integer DEFAULT nextval('"book_lid_seq"'::text) NOT NULL,
  "nid" integer NOT NULL,
  "body" text NOT NULL,
  "section" integer,
  "parent" integer NOT NULL,
  "weight" integer NOT NULL,
  "pid" integer NOT NULL,
  "log" text NOT NULL,
  Constraint "book_pkey" Primary Key ("lid")
);

--
-- TOC Entry ID 151 (OID 24087)
--
-- Name: book Type: ACL Owner:
--

REVOKE ALL on "book" from PUBLIC;
GRANT ALL on "book" to "root";
GRANT ALL on "book" to "wallaby";

--
-- TOC Entry ID 152 (OID 24321)
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
-- TOC Entry ID 153 (OID 24321)
--
-- Name: moderate Type: ACL Owner:
--

REVOKE ALL on "moderate" from PUBLIC;
GRANT ALL on "moderate" to "root";
GRANT ALL on "moderate" to "wallaby";

--
-- TOC Entry ID 154 (OID 24599)
--
-- Name: comments Type: TABLE Owner: root
--

CREATE TABLE "comments" (
  "cid" integer DEFAULT nextval('"comments_cid_seq"'::text) NOT NULL,
  "pid" integer NOT NULL,
  "lid" integer NOT NULL,
  "author" integer NOT NULL,
  "subject" character varying(64) NOT NULL,
  "comment" text NOT NULL,
  "hostname" character varying(128) NOT NULL,
  "timestamp" integer NOT NULL,
  "link" character varying(16) NOT NULL,
  Constraint "comments_pkey" Primary Key ("cid")
);

--
-- TOC Entry ID 155 (OID 24599)
--
-- Name: comments Type: ACL Owner:
--

REVOKE ALL on "comments" from PUBLIC;
GRANT ALL on "comments" to "root";
GRANT ALL on "comments" to "wallaby";

--
-- TOC Entry ID 156 (OID 20233)
--
-- Name: "mask_access_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "mask_access_ukey" on "access" using btree ( "mask" "varchar_ops" );

--
-- TOC Entry ID 157 (OID 20393)
--
-- Name: "title_boxes_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "title_boxes_ukey" on "boxes" using btree ( "title" "varchar_ops" );

--
-- TOC Entry ID 158 (OID 20393)
--
-- Name: "info_boxes_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "info_boxes_ukey" on "boxes" using btree ( "info" "varchar_ops" );

--
-- TOC Entry ID 159 (OID 20519)
--
-- Name: "name_category_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "name_category_ukey" on "category" using btree ( "name" "varchar_ops" );

--
-- TOC Entry ID 160 (OID 20563)
--
-- Name: "url_channel_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "url_channel_ukey" on "channel" using btree ( "url" "varchar_ops" );

--
-- TOC Entry ID 161 (OID 20563)
--
-- Name: "file_channel_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "file_channel_ukey" on "channel" using btree ( "file" "varchar_ops" );

--
-- TOC Entry ID 162 (OID 20563)
--
-- Name: "site_channel_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "site_channel_ukey" on "channel" using btree ( "site" "varchar_ops" );

--
-- TOC Entry ID 163 (OID 20699)
--
-- Name: "name_collection_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "name_collection_ukey" on "collection" using btree ( "name" "varchar_ops" );

--
-- TOC Entry ID 169 (OID 21193)
--
-- Name: "entry_namcoll_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "entry_namcoll_ukey" on "entry" using btree ( "name" "varchar_ops", "collection" "varchar_ops" );

--
-- TOC Entry ID 164 (OID 21244)
--
-- Name: "url_feed_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "url_feed_ukey" on "feed" using btree ( "url" "varchar_ops" );

--
-- TOC Entry ID 165 (OID 21244)
--
-- Name: "title_feed_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "title_feed_ukey" on "feed" using btree ( "title" "varchar_ops" );

--
-- TOC Entry ID 166 (OID 22553)
--
-- Name: "link_site_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "link_site_ukey" on "site" using btree ( "link" "varchar_ops" );

--
-- TOC Entry ID 167 (OID 22553)
--
-- Name: "name_site_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "name_site_ukey" on "site" using btree ( "name" "varchar_ops" );

--
-- TOC Entry ID 170 (OID 22660)
--
-- Name: "tag_namecoll_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "tag_namecoll_ukey" on "tag" using btree ( "name" "varchar_ops", "collections" "varchar_ops" );

--
-- TOC Entry ID 168 (OID 22699)
--
-- Name: "name_topic_ukey" Type: INDEX Owner: root
--

CREATE UNIQUE INDEX "name_topic_ukey" on "topic" using btree ( "name" "varchar_ops" );

--
-- TOC Entry ID 171 (OID 24321)
--
-- Name: "moderate_cid_key" Type: INDEX Owner: root
--

CREATE  INDEX "moderate_cid_key" on "moderate" using btree ( "cid" "int4_ops" );

--
-- TOC Entry ID 172 (OID 24321)
--
-- Name: "moderate_nid_key" Type: INDEX Owner: root
--

CREATE  INDEX "moderate_nid_key" on "moderate" using btree ( "nid" "int4_ops" );

--
-- TOC Entry ID 173 (OID 24599)
--
-- Name: "lid_comments_key" Type: INDEX Owner: root
--

CREATE  INDEX "lid_comments_key" on "comments" using btree ( "lid" "int4_ops" );

