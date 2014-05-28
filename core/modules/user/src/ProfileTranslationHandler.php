<?php

/**
 * @file
 * Contains \Drupal\user\ProfileTranslationHandler.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityInterface;
use Drupal\content_translation\ContentTranslationHandler;

/**
 * Defines the translation handler for users.
 */
class ProfileTranslationHandler extends ContentTranslationHandler {

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(array &$form, array &$form_state, EntityInterface $entity) {
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
  function entityFormSave(array $form, array &$form_state) {
    if ($this->getSourceLangcode($form_state)) {
      $entity = content_translation_form_controller($form_state)->getEntity();
      // We need a redirect here, otherwise we would get an access denied page
      // since the current URL would be preserved and we would try to add a
      // translation for a language that already has a translation.
      $form_state['redirect_route'] = $entity->urlInfo();
    }
  }
}
