<?php

declare(strict_types=1);

namespace Drupal\views_ui_test_field\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_ui_test_field.
 */
class ViewsUiTestFieldHooks {

  /**
   * Implements hook_form_FORM_ID_alter() for views_ui_add_handler_form.
   *
   * Changes the label for one of the tests fields to validate this label is not
   * searched on.
   */
  #[Hook('form_views_ui_add_handler_form_alter')]
  public function formViewsUiAddHandlerFormAlter(&$form, FormStateInterface $form_state) : void {
    $form['options']['name']['#options']['views.views_test_field_1']['title']['data']['#title'] .= ' FIELD_1_LABEL';
  }

}
