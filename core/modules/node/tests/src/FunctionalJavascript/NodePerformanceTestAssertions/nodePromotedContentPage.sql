SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."alias" LIKE "/node" ESCAPE '\\') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC
SELECT "name", "route", "fit" FROM "router" WHERE "pattern_outline" IN ( "/node" ) AND "number_parts" >= 1
SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "views.view.promoted_content" )
SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "views.settings" )
SELECT "config"."name" AS "name" FROM "config" "config" WHERE ("collection" = "") AND ("name" LIKE "core.entity_view_mode.%" ESCAPE '\\') ORDER BY "collection" ASC, "name" ASC
SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.entity_view_mode.node.full", "core.entity_view_mode.node.rss", "core.entity_view_mode.node.teaser", "core.entity_view_mode.user.compact", "core.entity_view_mode.user.full" )
SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "views.view_route_names" ) AND "collection" = "state"
SELECT "name", "route" FROM "router" WHERE "name" IN ( "view.promoted_content.feed_1" )
SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "router.path_roots" ) AND "collection" = "state"
SELECT 1 AS "expression" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/rss.xml%" ESCAPE '\\') LIMIT 1 OFFSET 0
SELECT COUNT(*) AS "expression" FROM (SELECT 1 AS "expression" FROM "node_field_data" "node_field_data" WHERE ("node_field_data"."promote" = 1) AND ("node_field_data"."status" = 1)) "subquery"
SELECT "node_field_data"."sticky" AS "node_field_data_sticky", "node_field_data"."created" AS "node_field_data_created", "node_field_data"."nid" AS "nid" FROM "node_field_data" "node_field_data" WHERE ("node_field_data"."promote" = 1) AND ("node_field_data"."status" = 1) ORDER BY "node_field_data_sticky" DESC, "node_field_data_created" DESC LIMIT 10 OFFSET 0
SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_uid" AS "revision_uid", "revision"."revision_timestamp" AS "revision_timestamp", "revision"."revision_log" AS "revision_log", "revision"."revision_default" AS "revision_default", "base"."nid" AS "nid", "base"."type" AS "type", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "node" "base" INNER JOIN "node_revision" "revision" ON "revision"."vid" = "base"."vid" WHERE "base"."nid" IN (1)
SELECT "node_field_data".*, "node_field_data"."langcode" AS "node_field_data__langcode", "node__body"."body_value" AS "body_value", "node__body"."body_format" AS "body_format" FROM "node_field_data" "node_field_data" LEFT OUTER JOIN "node__body" "node__body" ON "node__body"."entity_id" = "node_field_data"."nid" AND "node__body"."langcode" = "node_field_data"."langcode" AND "node__body"."deleted" = 0 WHERE "node_field_data"."nid" IN (1)
SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.entity_view_display.node.test_content.teaser", "core.entity_view_display.node.test_content.default" )
SELECT "config"."name" AS "name" FROM "config" "config" WHERE ("collection" = "") AND ("name" LIKE "node.type.%" ESCAPE '\\') ORDER BY "collection" ASC, "name" ASC
SELECT "base"."uid" AS "uid", "base"."uuid" AS "uuid", "base"."langcode" AS "langcode" FROM "users" "base" WHERE "base"."uid" IN (0)
SELECT "users_field_data".*, "users_field_data"."langcode" AS "users_field_data__langcode" FROM "users_field_data" "users_field_data" WHERE "users_field_data"."uid" IN (0)
SELECT "user__roles"."entity_id" AS "id", "user__roles"."langcode" AS "langcode", "user__roles"."roles_target_id" AS "roles_target_id", "user__roles"."delta" AS "roles_delta" FROM "user__roles" "user__roles" WHERE ("user__roles"."entity_id" IN (0)) AND ("user__roles"."deleted" = 0) ORDER BY "user__roles"."delta" ASC
SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.date_format.medium" )
SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.date_format.long" )
SELECT "name", "route" FROM "router" WHERE "name" IN ( "entity.node.canonical" )
SELECT 1 AS "expression" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/node%" ESCAPE '\\') LIMIT 1 OFFSET 0
SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.entity_view_display.user.user.compact", "core.entity_view_display.user.user.default" )
SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "filter.format.plain_text" )
SELECT "name", "route" FROM "router" WHERE "name" IN ( "view.promoted_content.page_1" )
INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("state:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")
DELETE FROM "semaphore"  WHERE ("name" = "state:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")
INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("theme_registry:runtime:stark:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")
DELETE FROM "semaphore"  WHERE ("name" = "theme_registry:runtime:stark:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")
INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("library_info:stark:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")
DELETE FROM "semaphore"  WHERE ("name" = "library_info:stark:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")
INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("path_alias_prefix_list:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")
DELETE FROM "semaphore"  WHERE ("name" = "path_alias_prefix_list:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")
