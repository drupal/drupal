<?php

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Tests\simpletest\Functional\SimpletestPhpunitBrowserTest;

/**
 * Test PHPUnit output for the Simpletest UI.
 *
 * @group simpletest
 *
 * @see \Drupal\Tests\Listeners\SimpletestUiPrinter
 */
class UiPhpUnitOutputTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = ['simpletest'];

  /**
   * Tests that PHPUnit output in the Simpletest UI looks good.
   */
  public function testOutput() {
    require_once __DIR__ . '/../../tests/fixtures/simpletest_phpunit_browsertest.php';
    $phpunit_junit_file = $this->container->get('file_system')->realpath('public://phpunit_junit.xml');
    // Prepare the default browser test output directory in the child site.
    $this->container->get('file_system')->mkdir('public://simpletest');
    $status = 0;
    $output = [];
    simpletest_phpunit_run_command([SimpletestPhpunitBrowserTest::class], $phpunit_junit_file, $status, $output);

    // Check that there are <br> tags for the HTML output by
    // SimpletestUiPrinter.
    $this->assertEqual($output[18], 'HTML output was generated<br />');
    // Check that URLs are printed as HTML links.
    $this->assertIdentical(strpos($output[19], '<a href="http'), 0);
  }

}
