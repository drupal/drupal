SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1
SELECT * FROM "users_field_data" "u" WHERE "u"."uid" = "2" AND "u"."default_langcode" = 1
SELECT "roles_target_id" FROM "user__roles" WHERE "entity_id" = "2"
SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("base_table"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))
