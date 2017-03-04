<?php

namespace Drupal\views_test_data\Controller;

/**
 * Controller class for views_test_data callbacks.
 */
class ViewsTestDataController {

  /**
   * Renders an error form page.
   *
   * This contains a form that will contain an error and an embedded view with
   * an exposed form.
   */
  public function errorFormPage() {
    $build = [];
    $build['view'] = [
      '#type' => 'view',
      '#name' => 'test_exposed_form_buttons',
    ];
    $build['error_form'] = \Drupal::formBuilder()->getForm('Drupal\views_test_data\Form\ViewsTestDataErrorForm');

    return $build;
  }

}
