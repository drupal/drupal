<?php

declare(strict_types=1);

namespace Drupal\default_admin_form_test\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for default_admin_form_test.
 */
class DefaultAdminFormTestHooks {

  /**
   * Implements hook_form_BASE_FORM_ID_alter() for node_form.
   *
   * Entity forms are allowed to set #tree, and some contributed ones do, so
   * the node form stands in for them here.
   */
  #[Hook('form_node_form_alter')]
  public function formNodeFormAlter(array &$form, FormStateInterface $form_state): void {
    $form['#tree'] = TRUE;
  }

}
