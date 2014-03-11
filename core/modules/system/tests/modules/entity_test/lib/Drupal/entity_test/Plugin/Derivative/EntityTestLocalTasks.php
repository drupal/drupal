<?php

/**
 * @file
 * Contains \Drupal\entity_test\Plugin\Derivative\EntityTestLocalTasks.
 */

namespace Drupal\entity_test\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeBase;

/**
 * Defines the local tasks for all the entity_test entities.
 */
class EntityTestLocalTasks extends DerivativeBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = array();
    $types = entity_test_entity_types();

    foreach($types as $entity_type) {
      $this->derivatives[$entity_type] = array();
      $this->derivatives[$entity_type]['base_route'] = "entity_test.edit_$entity_type";
      $this->derivatives[$entity_type]['route_name'] = "entity_test.edit_$entity_type";
      $this->derivatives[$entity_type]['title'] = 'Edit';
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
