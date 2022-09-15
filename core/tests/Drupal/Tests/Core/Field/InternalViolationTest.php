<?php

namespace Drupal\Tests\Core\Field;

use Drupal\Core\Field\InternalViolation;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * @coversDefaultClass \Drupal\Core\Field\InternalViolation
 * @group legacy
 */
class InternalViolationTest extends UnitTestCase {

  /**
   * @covers ::__get
   * @covers ::__set
   */
  public function testSetGetDynamicProperties() {
    $violation = new InternalViolation($this->prophesize(ConstraintViolationInterface::class)->reveal());
    $this->expectDeprecation('Setting dynamic properties on violations is deprecated in drupal:9.5.0 and is removed from drupal:11.0.0. See https://www.drupal.org/node/3307919');
    $this->expectDeprecation('Accessing dynamic properties on violations is deprecated in drupal:9.5.0 and is removed from drupal:11.0.0. See https://www.drupal.org/node/3307919');
    $violation->foo = 'bar';
    $this->assertSame('bar', $violation->foo);
  }

  /**
   * @covers ::__get
   * @covers ::__set
   */
  public function testSetGetArrayPropertyPath() {
    $violation = new InternalViolation($this->prophesize(ConstraintViolationInterface::class)->reveal());
    $this->expectDeprecation('Accessing the arrayPropertyPath property is deprecated in drupal:9.5.0 and is removed from drupal:11.0.0. Use \Symfony\Component\Validator\ConstraintViolationInterface::getPropertyPath() instead. See https://www.drupal.org/node/3307919');
    $violation->arrayPropertyPath = ['bar'];
    $this->assertSame(['bar'], $violation->arrayPropertyPath);
  }

}
