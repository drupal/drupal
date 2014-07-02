<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\Derivative\SelectionBase.
 */

namespace Drupal\entity_reference\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides derivative plugins for Entity Reference Selection plugins.
 *
 * @see \Drupal\entity_reference\Plugin\entity_reference\selection\SelectionBase
 * @see \Drupal\entity_reference\Plugin\Type\SelectionPluginManager
 * @see \Drupal\entity_reference\Annotation\EntityReferenceSelection
 * @see \Drupal\entity_reference\Plugin\Type\Selection\SelectionInterface
 * @see plugin_api
 */
class SelectionBase extends DeriverBase {
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $supported_entities = array(
      'comment',
      'file',
      'node',
      'taxonomy_term',
      'user'
    );
    foreach (\Drupal::entityManager()->getDefinitions() as $entity_type_id => $entity_type) {
      if (!in_array($entity_type_id, $supported_entities)) {
        $this->derivatives[$entity_type_id] = $base_plugin_definition;
        $this->derivatives[$entity_type_id]['label'] = t('@entity_type selection', array('@entity_type' => $entity_type->getLabel()));
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
