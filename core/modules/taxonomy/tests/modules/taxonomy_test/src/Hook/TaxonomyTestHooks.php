<?php

declare(strict_types=1);

namespace Drupal\taxonomy_test\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\taxonomy\TermInterface;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for taxonomy_test.
 */
class TaxonomyTestHooks {

  /**
   * Implements hook_entity_access().
   */
  #[Hook('entity_access')]
  public function entityAccess(EntityInterface $entity, string $operation, AccountInterface $account) : AccessResultInterface {
    if ($entity instanceof TermInterface) {
      $parts = explode(' ', (string) $entity->label());
      if (in_array('Inaccessible', $parts, TRUE) && in_array($operation, $parts, TRUE)) {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::neutral();
  }

  /**
   * Implements hook_query_alter().
   */
  #[Hook('query_alter')]
  public function queryAlter(AlterableInterface $query): void {
    $value = \Drupal::state()->get('taxonomy_test_query_alter');
    if (isset($value)) {
      \Drupal::state()->set('taxonomy_test_query_alter', ++$value);
    }
  }

  /**
   * Implements hook_query_TAG_alter().
   */
  #[Hook('query_term_access_alter')]
  public function queryTermAccessAlter(AlterableInterface $query): void {
    $value = \Drupal::state()->get('taxonomy_test_query_term_access_alter');
    if (isset($value)) {
      \Drupal::state()->set('taxonomy_test_query_term_access_alter', ++$value);
    }
  }

  /**
   * Implements hook_query_TAG_alter().
   */
  #[Hook('query_taxonomy_term_access_alter')]
  public function queryTaxonomyTermAccessAlter(AlterableInterface $query): void {
    $value = \Drupal::state()->get('taxonomy_test_query_taxonomy_term_access_alter');
    if (isset($value)) {
      \Drupal::state()->set('taxonomy_test_query_taxonomy_term_access_alter', ++$value);
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter() for the taxonomy term form.
   */
  #[Hook('form_taxonomy_term_form_alter')]
  public function formTaxonomyTermFormAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    if (\Drupal::state()->get('taxonomy_test.disable_parent_form_element', FALSE)) {
      $form['relations']['parent']['#disabled'] = TRUE;
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for the taxonomy term.
   */
  #[Hook('taxonomy_term_load')]
  public function taxonomyTermLoad($entities): void {
    $value = \Drupal::state()->get('taxonomy_test_taxonomy_term_load');
    // Only record loaded terms is the test has set this to an empty array.
    if (is_array($value)) {
      $value = array_merge($value, array_keys($entities));
      \Drupal::state()->set('taxonomy_test_taxonomy_term_load', array_unique($value));
    }
  }

}
