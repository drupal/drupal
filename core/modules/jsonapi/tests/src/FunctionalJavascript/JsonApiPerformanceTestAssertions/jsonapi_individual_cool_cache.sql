SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1
SELECT * FROM "users_field_data" "u" WHERE "u"."uid" = "2" AND "u"."default_langcode" = 1
SELECT "roles_target_id" FROM "user__roles" WHERE "entity_id" = "2"
SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."alias" LIKE "/jsonapi/node/article/677f9911-f002-4639-9891-5c39e8b00d9d" ESCAPE '\\') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC
SELECT "name", "route", "fit" FROM "router" WHERE "pattern_outline" IN ( "/jsonapi/node/article/677f9911-f002-4639-9891-5c39e8b00d9d", "/jsonapi/node/article/%", "/jsonapi/node/%/%", "/jsonapi/%/article/677f9911-f002-4639-9891-5c39e8b00d9d", "/jsonapi/%/%/%", "/jsonapi/node/article", "/jsonapi/node/%", "/jsonapi/%/article", "/jsonapi/node", "/jsonapi/%", "/jsonapi" ) AND "number_parts" >= 4
SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("base_table"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))
SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_uid" AS "revision_uid", "revision"."revision_timestamp" AS "revision_timestamp", "revision"."revision_log" AS "revision_log", "revision"."revision_default" AS "revision_default", "base"."nid" AS "nid", "base"."type" AS "type", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "node" "base" INNER JOIN "node_revision" "revision" ON "revision"."vid" = "base"."vid" WHERE "base"."nid" IN (1)
SELECT "node_field_data".*, "node_field_data"."langcode" AS "node_field_data__langcode", "node__body"."body_value" AS "body_value", "node__body"."body_format" AS "body_format" FROM "node_field_data" "node_field_data" LEFT OUTER JOIN "node__body" "node__body" ON "node__body"."entity_id" = "node_field_data"."nid" AND "node__body"."langcode" = "node_field_data"."langcode" AND "node__body"."deleted" = 0 WHERE "node_field_data"."nid" IN (1)
SELECT 1 AS "expression" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/jsonapi%" ESCAPE '\\') LIMIT 1 OFFSET 0
SELECT "name", "route" FROM "router" WHERE "name" IN ( "jsonapi.node--article.node_type.relationship.get" )
SELECT "name", "route" FROM "router" WHERE "name" IN ( "jsonapi.node--article.node_type.related" )
SELECT "name", "route" FROM "router" WHERE "name" IN ( "jsonapi.node--article.revision_uid.relationship.get" )
SELECT "name", "route" FROM "router" WHERE "name" IN ( "jsonapi.node--article.revision_uid.related" )
SELECT "name", "route" FROM "router" WHERE "name" IN ( "jsonapi.node--article.uid.relationship.get" )
SELECT "name", "route" FROM "router" WHERE "name" IN ( "jsonapi.node--article.uid.related" )
INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("path_alias_prefix_list:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")
DELETE FROM "semaphore"  WHERE ("name" = "path_alias_prefix_list:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")
