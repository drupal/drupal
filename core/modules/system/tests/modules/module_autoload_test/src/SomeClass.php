<?php

namespace Drupal\module_autoload_test;

class SomeClass {

  const TEST = '\Drupal\module_autoload_test\SomeClass::TEST';

  public function testMethod() {
    return 'Drupal\\module_autoload_test\\SomeClass::testMethod() was invoked.';
  }

}
