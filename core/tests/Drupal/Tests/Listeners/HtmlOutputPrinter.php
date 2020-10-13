<?php

namespace Drupal\Tests\Listeners;

use Drupal\TestTools\PhpUnitCompatibility\RunnerVersion;
use PHPUnit\Framework\TestResult;

// In order to manage different implementations across PHPUnit versions, we
// dynamically load the base ResultPrinter class dependent on the PHPUnit runner
// version.
if (!class_exists(ResultPrinterBase::class, FALSE)) {
  if (RunnerVersion::getMajor() < 9) {
    class_alias('PHPUnit\TextUI\ResultPrinter', ResultPrinterBase::class);
  }
  else {
    class_alias('PHPUnit\TextUI\DefaultResultPrinter', ResultPrinterBase::class);
  }
}

/**
 * Defines a class for providing html output results for functional tests.
 *
 * @internal
 */
class HtmlOutputPrinter extends ResultPrinterBase {

  use HtmlOutputPrinterTrait;

  /**
   * {@inheritdoc}
   */
  public function printResult(TestResult $result): void {
    parent::printResult($result);

    $this->printHtmlOutput();
  }

}
