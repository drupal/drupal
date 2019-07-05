<?php

namespace Drupal\views_test_data\Controller;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Controller class for views_test_data callbacks.
 */
class ViewsTestDataController implements TrustedCallbackInterface {

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

  /**
   * #lazy_builder callback; for testing purposes only.
   */
  public static function placeholderLazyBuilder() {
    // No-op.
    return [];
  }

  /**
   * Test pre_render function.
   *
   * @param array $element
   *   A render array.
   *
   * @return array
   *   The changed render array.
   */
  public static function preRender($element) {
    $element['#markup'] = '\Drupal\views_test_data\Controller\ViewsTestDataController::preRender executed';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['placeholderLazyBuilder', 'preRender'];
  }

}
