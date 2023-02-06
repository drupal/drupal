<?php

namespace Drupal\entity_test\Entity;

/**
 * Test entity class routes.
 *
 * @ContentEntityType(
 *   id = "entity_test_add_page",
 *   label = @Translation("Entity test route add page"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer entity_test content",
 *   base_table = "entity_test_add_page",
 *   render_cache = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *   },
 *   links = {
 *     "add-page" = "/entity_test_add_page/{user}/add",
 *     "add-form" = "/entity_test_add_page/add/{user}/form",
 *   },
 * )
 */
class EntityTestAddPage extends EntityTest {
}
