<?php

namespace Drupal\psr_0_test\Tests;

class ExampleTest extends \DrupalWebTestCase {

  public static function getInfo() {
    return array(
      'name' => 'PSR0 example test: PSR-0 in disabled modules.',
      'description' => 'We want to assert that this test case is being discovered.',
      'group' => 'SimpleTest',
    );
  }

  function testArithmetics() {
    $this->assert(1 + 1 == 2, '1 + 1 == 2');
  }
}
