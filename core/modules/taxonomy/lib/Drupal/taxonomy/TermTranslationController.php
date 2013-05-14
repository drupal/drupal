<?php

/**
 * @file
 * Definition of Drupal\taxonomy\TermTranslationController.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\translation_entity\EntityTranslationControllerNG;

/**
 * Defines the translation controller class for terms.
 */
class TermTranslationController extends EntityTranslationControllerNG {

  /**
   * Overrides EntityTranslationController::entityFormAlter().
   */
  public function entityFormAlter(array &$form, array &$form_state, EntityInterface $entity) {
    parent::entityFormAlter($form, $form_state, $entity);
    $form['actions']['submit']['#submit'][] = array($this, 'entityFormSave');
  }

  /**
   * Form submission handler for TermTranslationController::entityFormAlter().
   *
   * This handles the save action.
   *
   * @see \Drupal\Core\Entity\EntityFormController::build().
   */
  function entityFormSave(array $form, array &$form_state) {
    if ($this->getSourceLangcode($form_state)) {
      $entity = translation_entity_form_controller($form_state)->getEntity();
      // We need a redirect here, otherwise we would get an access denied page,
      // since the current URL would be preserved and we would try to add a
      // translation for a language that already has a translation.
      $form_state['redirect'] = $this->getEditPath($entity);
    }
  }

}
