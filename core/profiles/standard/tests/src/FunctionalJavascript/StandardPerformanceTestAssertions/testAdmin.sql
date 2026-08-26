SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1
SELECT * FROM "users_field_data" "u" WHERE "u"."uid" = "3" AND "u"."default_langcode" = 1
SELECT "roles_target_id" FROM "user__roles" WHERE "entity_id" = "3"
SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "theme:stark" ) AND "collection" = "config.entity.key_store.block"
