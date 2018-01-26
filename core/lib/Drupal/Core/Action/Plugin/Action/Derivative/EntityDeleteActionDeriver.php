<?php

namespace Drupal\Core\Action\Plugin\Action\Derivative;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an action deriver that finds entity types with delete form.
 *
 * @see \Drupal\Core\Action\Plugin\Action\DeleteAction
 */
class EntityDeleteActionDeriver extends EntityActionDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (empty($this->derivatives)) {
      $definitions = [];
      foreach ($this->getApplicableEntityTypes() as $entity_type_id => $entity_type) {
        $definition = $base_plugin_definition;
        $definition['type'] = $entity_type_id;
        $definition['label'] = $this->t('Delete @entity_type', ['@entity_type' => $entity_type->getSingularLabel()]);
        $definition['confirm_form_route_name'] = 'entity.' . $entity_type->id() . '.delete_multiple_form';
        $definitions[$entity_type_id] = $definition;
      }
      $this->derivatives = $definitions;
    }

    return $this->derivatives;
  }

  /**
   * {@inheritdoc}
   */
  protected function isApplicable(EntityTypeInterface $entity_type) {
    return $entity_type->hasLinkTemplate('delete-multiple-form');
  }

}
