<?php

namespace Drupal\Tests\Listeners;

use PHPUnit\Framework\TestResult;
use PHPUnit\TextUI\ResultPrinter;

if (class_exists('PHPUnit_Runner_Version') && version_compare(\PHPUnit_Runner_Version::id(), '6.0.0', '<')) {
  class_alias('Drupal\Tests\Listeners\Legacy\HtmlOutputPrinter', 'Drupal\Tests\Listeners\HtmlOutputPrinter');
  // Using an early return instead of a else does not work when using the
  // PHPUnit phar due to some weird PHP behavior (the class gets defined without
  // executing the code before it and so the definition is not properly
  // conditional).
}
else {
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
    public function __construct($out = NULL, $verbose = FALSE, $colors = self::COLOR_DEFAULT, $debug = FALSE, $numberOfColumns = 80, $reverse = FALSE) {
      parent::__construct($out, $verbose, $colors, $debug, $numberOfColumns, $reverse);

      $this->setUpHtmlOutput();
    }

    /**
     * {@inheritdoc}
     */
    public function printResult(TestResult $result) {
      parent::printResult($result);

      $this->printHtmlOutput();
    }

  }
}
