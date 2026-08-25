SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1
SELECT * FROM "users_field_data" "u" WHERE "u"."uid" = "3" AND "u"."default_langcode" = 1
SELECT "roles_target_id" FROM "user__roles" WHERE "entity_id" = "3"
SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "theme:stark" ) AND "collection" = "config.entity.key_store.block"
SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node_revision" "base_table" INNER JOIN (SELECT "subquery_base_table"."nid" AS "nid", MAX(subquery_base_table.vid) AS "maximum_revision_id" FROM "node_revision" "subquery_base_table" WHERE "nid" = "1" GROUP BY "subquery_base_table"."nid") "sq_base_table" ON base_table.nid = sq_base_table.nid AND base_table.vid = sq_base_table.maximum_revision_id INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE "node_field_data"."nid" = "1"
