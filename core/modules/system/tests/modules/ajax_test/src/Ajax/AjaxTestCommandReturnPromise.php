<?php

declare(strict_types=1);

namespace Drupal\ajax_test\Ajax;

use Drupal\Core\Ajax\AppendCommand;

/**
 * Test Ajax command.
 */
class AjaxTestCommandReturnPromise extends AppendCommand {

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return [
      'command' => 'ajaxCommandReturnPromise',
      'method' => 'append',
      'selector' => $this->selector,
      'data' => $this->getRenderedContent(),
      'settings' => $this->settings,
    ];
  }

}
