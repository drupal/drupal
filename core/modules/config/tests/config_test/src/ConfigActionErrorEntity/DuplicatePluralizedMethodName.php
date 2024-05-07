<?php

namespace Drupal\config_test\ConfigActionErrorEntity;

use Drupal\config_test\Entity\ConfigTest;
use Drupal\Core\Config\Action\Attribute\ActionMethod;

/**
 * Test entity class.
 */
class DuplicatePluralizedMethodName extends ConfigTest {

  #[ActionMethod(pluralize: 'testMethod')]
  public function testMethod() {
  }

}
