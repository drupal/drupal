<?php

namespace Drupal\entity_test;

use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the test entity edit forms.
 */
class EntityTestForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    if (empty($this->entity->name->value)) {
      // Assign a random name to new EntityTest entities, to avoid repetition in
      // tests.
      $random = new Random();
      $this->entity->name->value = $random->name();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;

    // @todo: Is there a better way to check if an entity type is revisionable?
    if ($entity->getEntityType()->hasKey('revision') && !$entity->isNew()) {
      $form['revision'] = array(
        '#type' => 'checkbox',
        '#title' => t('Create new revision'),
        '#default_value' => $entity->isNewRevision(),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    try {
      $entity = $this->entity;

      // Save as a new revision if requested to do so.
      if (!$form_state->isValueEmpty('revision')) {
        $entity->setNewRevision();
      }

      $is_new = $entity->isNew();
      $entity->save();

      if ($is_new) {
        $message = t('%entity_type @id has been created.', array('@id' => $entity->id(), '%entity_type' => $entity->getEntityTypeId()));
      }
      else {
        $message = t('%entity_type @id has been updated.', array('@id' => $entity->id(), '%entity_type' => $entity->getEntityTypeId()));
      }
      drupal_set_message($message);

      if ($entity->id()) {
        $entity_type = $entity->getEntityTypeId();
        $form_state->setRedirect(
          "entity.$entity_type.edit_form",
          array($entity_type => $entity->id())
        );
      }
      else {
        // Error on save.
        drupal_set_message(t('The entity could not be saved.'), 'error');
        $form_state->setRebuild();
      }
    }
    catch (\Exception $e) {
      \Drupal::state()->set('entity_test.form.save.exception', get_class($e) . ': ' . $e->getMessage());
    }
  }

}
