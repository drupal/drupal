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
    $form = parent::buildForm($form, $form_state);
    $definition = $this->entityManager->getDefinition($this->entityType);
    $form['#title'] = $this->t('Add new %label @entity-type', array('%label' => $definition->getLabel(), '@entity-type' => $this->entityInfo->getLowercaseLabel()));
    return $form;
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
    if (!$definition->isFieldable() || !$definition->hasController('view_builder')) {
      throw new NotFoundHttpException();
    }

    $this->entity->targetEntityType = $this->entityType;
  }

}
