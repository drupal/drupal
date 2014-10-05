<?php

/**
 * @file
 * Contains \Drupal\content_translation\Form\ContentTranslationDeleteForm.
 */

namespace Drupal\content_translation\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Delete translation form for content_translation module.
 */
class ContentTranslationDeleteForm extends ConfirmFormBase {

  /**
   * The entity whose translation is being deleted.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The language of the translation being deleted.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $language;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_translation_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $language = NULL) {
    $this->entity = $this->getRequest()->attributes->get($entity_type_id);
    $this->language = language_load($language);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @language translation of %label?', array('@language' => $this->language->name, '%label' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->urlInfo('drupal:content-translation-overview');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove the translated values.
    $this->entity = $this->entity->getUntranslated();
    $this->entity->removeTranslation($this->language->id);
    $this->entity->save();

    // Remove any existing path alias for the removed translation.
    // @todo This should be taken care of by the Path module.
    if (\Drupal::moduleHandler()->moduleExists('path')) {
      $path = $this->entity->getSystemPath();
      $conditions = array('source' => $path, 'langcode' => $this->language->id);
      \Drupal::service('path.alias_storage')->delete($conditions);
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
