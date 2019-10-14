<?php

namespace Drupal\TestTools\PhpUnitCompatibility\PhpUnit6;

/**
 * Defines a class for providing html output links in the Simpletest UI.
 */
class SimpletestUiPrinter extends HtmlOutputPrinter {

  /**
   * {@inheritdoc}
   */
  public function write($buffer) {
    $this->simpletestUiWrite($buffer);
  }

}
