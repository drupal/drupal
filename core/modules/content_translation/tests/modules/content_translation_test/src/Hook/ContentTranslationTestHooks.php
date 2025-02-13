<?php

declare(strict_types=1);

namespace Drupal\content_translation_test\Hook;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for content_translation_test.
 */
class ContentTranslationTestHooks {

  /**
   * Implements hook_entity_bundle_info_alter().
   */
  #[Hook('entity_bundle_info_alter')]
  public function entityBundleInfoAlter(&$bundles): void {
    // Store the initial status of the "translatable" property for the
    // "entity_test_mul" bundle.
    $translatable = !empty($bundles['entity_test_mul']['entity_test_mul']['translatable']);
    \Drupal::state()->set('content_translation_test.translatable', $translatable);
    // Make it translatable if Content Translation did not. This will make the
    // entity object translatable even if it is disabled in Content Translation
    // settings.
    if (!$translatable) {
      $bundles['entity_test_mul']['entity_test_mul']['translatable'] = TRUE;
    }
  }

  /**
   * Implements hook_entity_access().
   */
  #[Hook('entity_access')]
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    $access = \Drupal::state()->get('content_translation.entity_access.' . $entity->getEntityTypeId());
    if (!empty($access[$operation])) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::neutral();
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   *
   * Adds a textfield to node forms based on a request parameter.
   */
  #[Hook('form_node_form_alter')]
  public function formNodeFormAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    $langcode = $form_state->getFormObject()->getFormLangcode($form_state);
    if (in_array($langcode, ['en', 'fr']) && \Drupal::request()->get('test_field_only_en_fr')) {
      $form['test_field_only_en_fr'] = [
        '#type' => 'textfield',
        '#title' => 'Field only available on the english and french form',
      ];
      foreach (array_keys($form['actions']) as $action) {
        if ($action != 'preview' && isset($form['actions'][$action]['#type']) && $form['actions'][$action]['#type'] === 'submit') {
          $form['actions'][$action]['#submit'][] = [$this, 'formNodeFormSubmit'];
        }
      }
    }
  }

  /**
   * Implements hook_entity_translation_delete().
   */
  #[Hook('entity_translation_delete')]
  public function entityTranslationDelete(EntityInterface $translation): void {
    \Drupal::state()->set('content_translation_test.translation_deleted', TRUE);
  }

  /**
   * Form submission handler for custom field added based on a request parameter.
   *
   * @see content_translation_test_form_node_article_form_alter()
   */
  public function formNodeFormSubmit($form, FormStateInterface $form_state): void {
    \Drupal::state()->set('test_field_only_en_fr', $form_state->getValue('test_field_only_en_fr'));
  }

}
