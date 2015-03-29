<?php

namespace Drupal\simpletest\Tests;

class PSR4WebTest extends \DrupalWebTestCase {

  public static function getInfo() {
    return array(
      'name' => 'PSR4 web test',
      'description' => 'We want to assert that this PSR-4 test case is being discovered.',
      'group' => 'SimpleTest',
    );
  }

  function testArithmetics() {
    $this->assert(1 + 1 == 2, '1 + 1 == 2');
  }
}
