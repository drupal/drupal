<?php

/**
 * @file
 * Contains \Drupal\entity\Form\EntityDisplayModeDeleteForm.
 */

namespace Drupal\entity\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Provides the delete form for entity display modes.
 */
class EntityDisplayModeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'entity.' . $this->entity->entityType() . '_list',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity_info = $this->entity->entityInfo();
    return t('Are you sure you want to delete the %label @entity-type?', array('%label' => $this->entity->label(), '@entity-type' => strtolower($entity_info['label'])));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $entity_info = $this->entity->entityInfo();
    return t('Deleting a @entity-type will cause any output still requesting to use that @entity-type to use the default display settings.', array('@entity-type' => strtolower($entity_info['label'])));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    parent::submit($form, $form_state);

    $entity_info = $this->entity->entityInfo();
    drupal_set_message(t('Deleted the %label @entity-type.', array('%label' => $this->entity->label(), '@entity-type' => strtolower($entity_info['label']))));
    $this->entity->delete();
    entity_info_cache_clear();
    $short_type = str_replace('_mode', '', $this->entity->entityType());
    $form_state['redirect'] = "admin/structure/display-modes/$short_type";
  }

}
