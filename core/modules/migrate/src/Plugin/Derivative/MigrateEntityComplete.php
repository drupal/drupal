<?php

namespace Drupal\migrate\Plugin\Derivative;

use Drupal\migrate\Plugin\migrate\destination\EntityContentComplete;

/**
 * Deriver for entity_complete:ENTITY_TYPE entity migrations.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3533565
 */
class MigrateEntityComplete extends MigrateEntity {

  public function __construct(array $entity_definitions) {
    @trigger_error(__CLASS__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533565', E_USER_DEPRECATED);
    parent::__construct($entity_definitions);
  }

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
