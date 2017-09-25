<?php

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests if Simpletest-based tests are skipped based on module requirements.
 *
 * This test should always be skipped when TestDiscovery is used to discover it.
 * This means that if you specify this test to run-tests.sh with --class or
 * --file, this test will run and fail.
 *
 * @dependencies module_does_not_exist
 *
 * @group simpletest
 *
 * @todo Change or remove this test when Simpletest-based tests are able to skip
 *       themselves based on requirements.
 * @see https://www.drupal.org/node/1273478
 */
class SkipRequiredModulesTest extends WebTestBase {

  public function testModuleNotFound() {
    $this->fail('This test should have been skipped during discovery.');
  }

}
