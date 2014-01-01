<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\Derivative\SelectionBase.
 */

namespace Drupal\entity_reference\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeBase;

/**
 * Base class for selection plugins provided by Entity Reference.
 */
class SelectionBase extends DerivativeBase {
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    $supported_entities = array(
      'comment',
      'file',
      'node',
      'taxonomy_term',
      'user'
    );
    foreach (\Drupal::entityManager()->getDefinitions() as $entity_type => $info) {
      if (!in_array($entity_type, $supported_entities)) {
        $this->derivatives[$entity_type] = $base_plugin_definition;
        $this->derivatives[$entity_type]['label'] = t('@entity_type selection', array('@entity_type' => $info->getLabel()));
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
