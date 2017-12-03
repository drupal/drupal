<?php

namespace Drupal\js_ajax_test\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Test Ajax command.
 */
class JsAjaxTestCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'jsAjaxTestCommand',
      'selector' => '#js_ajax_test_form_wrapper',
    ];
  }

}
