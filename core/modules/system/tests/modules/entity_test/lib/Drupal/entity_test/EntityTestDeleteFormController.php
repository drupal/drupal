<?php

/**
 * @file
 * Contains \Drupal\entity_test\EntityTestDeleteFormController.
 */

namespace Drupal\entity_test;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;

/**
 * Provides the entity_test delete form.
 */
class EntityTestDeleteFormController extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => '<front>',
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
  public function submit(array $form, array &$form_state) {
    parent::submit($form, $form_state);
    $entity = $this->entity;
    $entity->delete();
    drupal_set_message(t('%entity_type @id has been deleted.', array('@id' => $entity->id(), '%entity_type' => $entity->getEntityTypeId())));
    $form_state['redirect_route']['route_name'] = '<front>';
  }

}
