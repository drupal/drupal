<?php

namespace Drupal\migrate\Plugin\Derivative;

use Drupal\migrate\Plugin\migrate\destination\EntityContentComplete;

/**
 * Deriver for entity_complete:ENTITY_TYPE entity migrations.
 */
class MigrateEntityComplete extends MigrateEntity {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityDefinitions as $entity_type => $entity_info) {
      $this->derivatives[$entity_type] = [
        'id' => "entity_complete:$entity_type",
        'class' => EntityContentComplete::class,
        'requirements_met' => 1,
        'provider' => $entity_info->getProvider(),
      ];
    }
    return $this->derivatives;
  }

}
