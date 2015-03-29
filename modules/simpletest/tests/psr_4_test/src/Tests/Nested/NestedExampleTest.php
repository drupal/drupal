<?php

namespace Drupal\psr_4_test\Tests\Nested;

class NestedExampleTest extends \DrupalWebTestCase {

  public static function getInfo() {
    return array(
      'name' => 'PSR4 example test: PSR-4 in nested subfolders.',
      'description' => 'We want to assert that this PSR-4 test case is being discovered.',
      'group' => 'SimpleTest',
    );
  }

  function testArithmetics() {
    $this->assert(1 + 1 == 2, '1 + 1 == 2');
  }
}
