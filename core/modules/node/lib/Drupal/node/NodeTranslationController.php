<?php

/**
 * @file
 * Definition of Drupal\node\NodeTranslationController.
 */

namespace Drupal\node;

use Drupal\Core\Entity\EntityInterface;
use Drupal\translation_entity\EntityTranslationController;

/**
 * Defines the translation controller class for nodes.
 */
class NodeTranslationController extends EntityTranslationController {

  /**
   * Overrides EntityTranslationController::getAccess().
   */
  public function getAccess(EntityInterface $entity, $op) {
    return node_access($op, $entity);
  }

  /**
   * Overrides EntityTranslationController::entityFormAlter().
   */
  public function entityFormAlter(array &$form, array &$form_state, EntityInterface $entity) {
    parent::entityFormAlter($form, $form_state, $entity);

    // Move the translation fieldset to a vertical tab.
    if (isset($form['translation_entity'])) {
      $form['translation_entity'] += array(
        '#group' => 'additional_settings',
        '#weight' => 100,
        '#attributes' => array(
          'class' => array('node-translation-options'),
        ),
      );

      // We do not need to show these values on node forms: they inherit the
      // basic node property values.
      $form['translation_entity']['status']['#access'] = FALSE;
      $form['translation_entity']['name']['#access'] = FALSE;
      $form['translation_entity']['created']['#access'] = FALSE;
    }
  }

  /**
   * Overrides EntityTranslationController::entityFormTitle().
   */
  protected function entityFormTitle(EntityInterface $entity) {
    $type_name = node_get_type_label($entity);
    return t('<em>Edit @type</em> @title', array('@type' => $type_name, '@title' => $entity->label()));
  }

  /**
   * Overrides EntityTranslationController::entityFormEntityBuild().
   */
  public function entityFormEntityBuild($entity_type, EntityInterface $entity, array $form, array &$form_state) {
    if (isset($form_state['values']['translation_entity'])) {
      $form_controller = translation_entity_form_controller($form_state);
      $translation = &$form_state['values']['translation_entity'];
      $translation['status'] = $form_controller->getEntity()->status;
      $translation['name'] = $form_state['values']['name'];
      $translation['created'] = $form_state['values']['date'];
    }
    parent::entityFormEntityBuild($entity_type, $entity, $form, $form_state);
  }

}
