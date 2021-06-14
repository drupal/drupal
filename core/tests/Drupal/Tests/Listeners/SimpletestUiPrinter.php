<?php

namespace Drupal\Tests\Listeners;

/**
 * Defines a class for providing html output links in the Simpletest UI.
 */
class SimpletestUiPrinter extends HtmlOutputPrinter {

  /**
   * {@inheritdoc}
   */
  public function write(string $buffer): void {
    $this->simpletestUiWrite($buffer);
  }

}
