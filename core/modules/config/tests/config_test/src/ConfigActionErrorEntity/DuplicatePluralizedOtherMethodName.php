<?php

declare(strict_types=1);

namespace Drupal\config_test\ConfigActionErrorEntity;

use Drupal\config_test\Entity\ConfigTest;
use Drupal\Core\Config\Action\Attribute\ActionMethod;

/**
 * Test entity class.
 */
class DuplicatePluralizedOtherMethodName extends ConfigTest {

  #[ActionMethod(pluralize: 'testMethod2')]
  public function testMethod() {
  }

  #[ActionMethod()]
  public function testMethod2() {
  }

}
