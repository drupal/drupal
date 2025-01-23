<?php

declare(strict_types=1);

namespace Drupal\entity_test\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\entity_test\EntityTestHelper;
use Drupal\entity_test\EntityTestTypesFilter;

/**
 * Defines the local tasks for all the entity_test entities.
 */
class EntityTestLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    $types = EntityTestHelper::getEntityTypes(EntityTestTypesFilter::Routing);

    foreach ($types as $entity_type) {
      $this->derivatives[$entity_type . '.canonical'] = [];
      $this->derivatives[$entity_type . '.canonical']['base_route'] = "entity.$entity_type.canonical";
      $this->derivatives[$entity_type . '.canonical']['route_name'] = "entity.$entity_type.canonical";
      $this->derivatives[$entity_type . '.canonical']['title'] = 'View';

      $this->derivatives[$entity_type . '.edit'] = [];
      $this->derivatives[$entity_type . '.edit']['base_route'] = "entity.$entity_type.canonical";
      $this->derivatives[$entity_type . '.edit']['route_name'] = "entity.$entity_type.edit_form";
      $this->derivatives[$entity_type . '.edit']['title'] = 'Edit';
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
