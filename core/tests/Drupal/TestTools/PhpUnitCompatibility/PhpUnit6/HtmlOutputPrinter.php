<?php

namespace Drupal\TestTools\PhpUnitCompatibility\PhpUnit6;

use Drupal\Tests\Listeners\HtmlOutputPrinterTrait;
use PHPUnit\Framework\TestResult;
use PHPUnit\TextUI\ResultPrinter;

/**
 * Defines a class for providing html output results for functional tests.
 *
 * @internal
 */
class HtmlOutputPrinter extends ResultPrinter {

  use HtmlOutputPrinterTrait;

  /**
   * {@inheritdoc}
   */
  public function printResult(TestResult $result) {
    parent::printResult($result);

    $this->printHtmlOutput();
  }

}
