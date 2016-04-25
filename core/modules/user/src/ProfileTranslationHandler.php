<?php

namespace Drupal\user;

use Drupal\Core\Entity\EntityInterface;
use Drupal\content_translation\ContentTranslationHandler;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the translation handler for users.
 */
class ProfileTranslationHandler extends ContentTranslationHandler {

  /**
   * {@inheritdoc}
   */
  protected function hasPublishedStatus() {
    // User status has nothing to do with translations visibility.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function hasCreatedTime() {
    // User creation date has nothing to do with translation creation date.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(array &$form, FormStateInterface $form_state, EntityInterface $entity) {
    parent::entityFormAlter($form, $form_state, $entity);
    $form['actions']['submit']['#submit'][] = array($this, 'entityFormSave');
  }

  /**
   * Form submission handler for ProfileTranslationHandler::entityFormAlter().
   *
   * This handles the save action.
   *
   * @see \Drupal\Core\Entity\EntityForm::build().
   */
  public function entityFormSave(array $form, FormStateInterface $form_state) {
    if ($this->getSourceLangcode($form_state)) {
      $entity = $form_state->getFormObject()->getEntity();
      // We need a redirect here, otherwise we would get an access denied page
      // since the current URL would be preserved and we would try to add a
      // translation for a language that already has a translation.
      $form_state->setRedirectUrl($entity->urlInfo());
    }
  }

}
