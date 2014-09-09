<?php
/**
 * @file
 * Definition of Drupal\entity_test\EntityTestForm.
 */

namespace Drupal\entity_test;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Form controller for the test entity edit forms.
 */
class EntityTestForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#default_value' => $entity->name->value,
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#weight' => -10,
    );

    $form['user_id'] = array(
      '#type' => 'textfield',
      '#title' => 'UID',
      '#default_value' => $entity->user_id->target_id,
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#weight' => -10,
    );

    $form['langcode'] = array(
      '#title' => t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->id,
      '#languages' => LanguageInterface::STATE_ALL,
    );

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
      $form_state['rebuild'] = TRUE;
    }
  }

}
