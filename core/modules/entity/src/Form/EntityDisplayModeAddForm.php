<?php

/**
 * @file
 * Contains \Drupal\entity\Form\EntityDisplayModeAddForm.
 */

namespace Drupal\entity\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides the add form for entity display modes.
 */
class EntityDisplayModeAddForm extends EntityDisplayModeFormBase {

  /**
   * The entity type for which the display mode is being created.
   *
   * @var string
   */
  protected $targetEntityTypeId;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    $this->targetEntityTypeId = $entity_type_id;
    $form = parent::buildForm($form, $form_state);
    // Change replace_pattern to avoid undesired dots.
    $form['id']['#machine_name']['replace_pattern'] = '[^a-z0-9_]+';
    $definition = $this->entityManager->getDefinition($this->targetEntityTypeId);
    $form['#title'] = $this->t('Add new %label @entity-type', array('%label' => $definition->getLabel(), '@entity-type' => $this->entityType->getLowercaseLabel()));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);

    form_set_value($form['id'], $this->targetEntityTypeId . '.' . $form_state['values']['id'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    $definition = $this->entityManager->getDefinition($this->targetEntityTypeId);
    if (!$definition->isFieldable() || !$definition->hasViewBuilderClass()) {
      throw new NotFoundHttpException();
    }

    $this->entity->targetEntityType = $this->targetEntityTypeId;
  }

}
