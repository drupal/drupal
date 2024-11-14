<?php

declare(strict_types=1);

namespace Drupal\views_form_test\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_form_test.
 */
class ViewsFormTestHooks {

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_views_form_media_media_page_list_alter')]
  public function formViewsFormMediaMediaPageListAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    $state = \Drupal::state();
    $count = $state->get('hook_form_BASE_FORM_ID_alter_count', 0);
    $state->set('hook_form_BASE_FORM_ID_alter_count', $count + 1);
  }

}
