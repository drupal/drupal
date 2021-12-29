<?php

namespace Drupal\Tests\Listeners;

use PHPUnit\Framework\TestResult;
use PHPUnit\TextUI\DefaultResultPrinter;

/**
 * Defines a class for providing html output results for functional tests.
 *
 * @internal
 */
class HtmlOutputPrinter extends DefaultResultPrinter {

  use HtmlOutputPrinterTrait;

  /**
   * {@inheritdoc}
   */
  public function printResult(TestResult $result): void {
    parent::printResult($result);

    $this->printHtmlOutput();
  }

}
