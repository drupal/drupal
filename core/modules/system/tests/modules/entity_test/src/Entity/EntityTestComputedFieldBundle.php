<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Defines the Test entity computed field bundle configuration entity.
 *
 * @ConfigEntityType(
 *   id = "entity_test_comp_field_bundle",
 *   label = @Translation("Test entity computed field bundle"),
 *   handlers = {
 *     "access" = "\Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "entity_test_comp_field_bundle",
 *   bundle_of = "entity_test_computed_field",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *   },
 * )
 */
class EntityTestComputedFieldBundle extends EntityTestBundle implements EntityDescriptionInterface {
}
