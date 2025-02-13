<?php

declare(strict_types=1);

namespace Drupal\views_test_data\Hook;

use Drupal\views\ViewEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_test_data.
 */
class ViewsTestDataHooks {

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_views_form_test_form_multiple_default_alter')]
  public function formViewsFormTestFormMultipleDefaultAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    \Drupal::messenger()->addStatus('Test base form ID with Views forms and arguments.');
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for the 'view' entity type.
   */
  #[Hook('view_update')]
  public function viewUpdate(ViewEntityInterface $view): void {
    // Use state to keep track of how many times a file is saved.
    $view_save_count = \Drupal::state()->get('views_test_data.view_save_count', []);
    $view_save_count[$view->id()] = isset($view_save_count[$view->id()]) ? $view_save_count[$view->id()] + 1 : 1;
    \Drupal::state()->set('views_test_data.view_save_count', $view_save_count);
  }

}
