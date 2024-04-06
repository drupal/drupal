<?php

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\Row;

/**
 * Provides entity base field override destination plugin.
 *
 * Base fields are non-configurable fields that always exist on a given entity
 * type, like the 'title', 'created' and 'sticky' fields of the 'node' entity
 * type. Some entity types can have bundles, for example the node content types.
 * The base fields exist on all bundles but the bundles can override the
 * definitions. For example, the label for node 'title' base field can be
 * different on different content types.
 *
 * Example:
 *
 * The example below migrates the node 'sticky' settings for each content type.
 * @code
 * id: d6_node_setting_sticky
 * label: Node type 'sticky' setting
 * migration_tags:
 *   - Drupal 6
 * source:
 *   plugin: d6_node_type
 *   constants:
 *     entity_type: node
 *     field_name: sticky
 * process:
 *   entity_type: 'constants/entity_type'
 *   bundle: type
 *   field_name: 'constants/field_name'
 *   label:
 *     plugin: default_value
 *     default_value: 'Sticky at the top of lists'
 *   'default_value/0/value': 'options/sticky'
 * destination:
 *   plugin: entity:base_field_override
 * migration_dependencies:
 *   required:
 *     - d6_node_type
 * @endcode
 */
#[MigrateDestination('entity:base_field_override')]
class EntityBaseFieldOverride extends EntityConfigBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntityId(Row $row) {
    $entity_type = $row->getDestinationProperty('entity_type');
    $bundle = $row->getDestinationProperty('bundle');
    $field_name = $row->getDestinationProperty('field_name');
    return "$entity_type.$bundle.$field_name";
  }

}
