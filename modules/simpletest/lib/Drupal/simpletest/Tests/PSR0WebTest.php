<?php

namespace Drupal\simpletest\Tests;

class PSR0WebTest extends \DrupalWebTestCase {

  public static function getInfo() {
    return array(
      'name' => 'PSR0 web test',
      'description' => 'We want to assert that this PSR-0 test case is being discovered.',
      'group' => 'SimpleTest',
    );
  }

  function testArithmetics() {
    $this->assert(1 + 1 == 2, '1 + 1 == 2');
  }
}
