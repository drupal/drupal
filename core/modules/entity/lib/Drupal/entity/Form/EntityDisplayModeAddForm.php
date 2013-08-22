<?php

/**
 * @file
 * Contains \Drupal\entity\Form\EntityDisplayModeAddForm.
 */

namespace Drupal\entity\Form;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides the add form for entity display modes.
 */
class EntityDisplayModeAddForm extends EntityDisplayModeFormBase {

  /**
   * @var string
   */
  protected $entityType;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $entity_type = NULL) {
    $this->entityType = $entity_type;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);

    form_set_value($form['id'], $this->entityType . '.' . $form_state['values']['id'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    $definition = $this->entityManager->getDefinition($this->entityType);
    if (!$definition['fieldable'] || !isset($definition['controllers']['render'])) {
      throw new NotFoundHttpException();
    }

    drupal_set_title(t('Add new %label @entity-type', array('%label' => $definition['label'], '@entity-type' => strtolower($this->entityInfo['label']))), PASS_THROUGH);
    $this->entity->targetEntityType = $this->entityType;
  }

}
