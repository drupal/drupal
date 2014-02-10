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
      'route_name' => 'entity.' . $this->entity->getEntityTypeId() . '_list',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity_type = $this->entity->getEntityType();
    return t('Are you sure you want to delete the %label @entity-type?', array('%label' => $this->entity->label(), '@entity-type' => $entity_type->getLowercaseLabel()));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $entity_type = $this->entity->getEntityType();
    return t('Deleting a @entity-type will cause any output still requesting to use that @entity-type to use the default display settings.', array('@entity-type' => $entity_type->getLowercaseLabel()));
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

    $entity_type = $this->entity->getEntityType();
    drupal_set_message(t('Deleted the %label @entity-type.', array('%label' => $this->entity->label(), '@entity-type' => $entity_type->getLowercaseLabel())));
    $this->entity->delete();
    entity_info_cache_clear();
    $form_state['redirect_route']['route_name'] = 'entity.' . $this->entity->getEntityTypeId() . '_list';
  }

}
