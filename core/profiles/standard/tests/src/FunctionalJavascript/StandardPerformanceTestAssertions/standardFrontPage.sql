SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."alias" LIKE "/admin/welcome" ESCAPE '\\') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC
SELECT "name", "route", "fit" FROM "router" WHERE "pattern_outline" IN ( "/admin/welcome", "/admin/%", "/admin" ) AND "number_parts" >= 2
SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "standard.show_welcome" ) AND "collection" = "state"
INSERT INTO "watchdog" ("uid", "type", "message", "variables", "severity", "link", "location", "referer", "hostname", "timestamp") VALUES (0, "access denied", "Path: @uri. %type: @message in %function (line %line of %file).", "WATCHDOG_DATA", 4, "", "LOCATION", "REFERER", "CLIENT_IP", "TIMESTAMP")
SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."alias" LIKE "/system/403" ESCAPE '\\') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC
SELECT "name", "route", "fit" FROM "router" WHERE "pattern_outline" IN ( "/system/403", "/system/%", "/system" ) AND "number_parts" >= 2
SELECT "name", "route" FROM "router" WHERE "name" IN ( "<current>" )
SELECT 1 AS "expression" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/admin%" ESCAPE '\\') LIMIT 1 OFFSET 0
SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "theme:stark" ) AND "collection" = "config.entity.key_store.block"
SELECT "name", "route" FROM "router" WHERE "name" IN ( "system.403" )
SELECT 1 AS "expression" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/system%" ESCAPE '\\') LIMIT 1 OFFSET 0
INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("state:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")
DELETE FROM "semaphore"  WHERE ("name" = "state:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")
INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("theme_registry:runtime:stark:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")
DELETE FROM "semaphore"  WHERE ("name" = "theme_registry:runtime:stark:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")
INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("library_info:stark:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")
DELETE FROM "semaphore"  WHERE ("name" = "library_info:stark:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")
INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("path_alias_prefix_list:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")
DELETE FROM "semaphore"  WHERE ("name" = "path_alias_prefix_list:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")
