<?php

namespace Drupal\psr_4_test\Tests;

class ExampleTest extends \DrupalWebTestCase {

  public static function getInfo() {
    return array(
      'name' => 'PSR4 example test: PSR-4 in disabled modules.',
      'description' => 'We want to assert that this test case is being discovered.',
      'group' => 'SimpleTest',
    );
  }

  function testArithmetics() {
    $this->assert(1 + 1 == 2, '1 + 1 == 2');
  }
}
