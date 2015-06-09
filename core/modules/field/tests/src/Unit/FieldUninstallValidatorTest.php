<?php

/**
 * @file
 * Contains \Drupal\Tests\field\Unit\FieldUninstallValidatorTest.
 */

namespace Drupal\Tests\field\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\field\FieldUninstallValidator
 * @group field
 */
class FieldUninstallValidatorTest extends UnitTestCase {

  /**
   * @var \Drupal\field\FieldUninstallValidator|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $fieldUninstallValidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->fieldUninstallValidator = $this->getMockBuilder('Drupal\field\FieldUninstallValidator')
      ->disableOriginalConstructor()
      ->setMethods(['getFieldStoragesByModule'])
      ->getMock();
    $this->fieldUninstallValidator->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * @covers ::validate
   */
  public function testValidateNoStorages() {
    $this->fieldUninstallValidator->expects($this->once())
      ->method('getFieldStoragesByModule')
      ->willReturn([]);

    $module = $this->randomMachineName();
    $expected = [];
    $reasons = $this->fieldUninstallValidator->validate($module);
    $this->assertSame($expected, $reasons);
  }

  /**
   * @covers ::validate
   */
  public function testValidateDeleted() {
    $field_storage = $this->getMockBuilder('Drupal\field\Entity\FieldStorageConfig')
      ->disableOriginalConstructor()
      ->getMock();
    $field_storage->expects($this->once())
      ->method('isDeleted')
      ->willReturn(TRUE);
    $this->fieldUninstallValidator->expects($this->once())
      ->method('getFieldStoragesByModule')
      ->willReturn([$field_storage]);

    $module = $this->randomMachineName();
    $expected = ['Fields pending deletion'];
    $reasons = $this->fieldUninstallValidator->validate($module);
    $this->assertSame($expected, $reasons);
  }

  /**
   * @covers ::validate
   */
  public function testValidateNoDeleted() {
    $field_storage = $this->getMockBuilder('Drupal\field\Entity\FieldStorageConfig')
      ->disableOriginalConstructor()
      ->getMock();
    $field_storage->expects($this->once())
      ->method('isDeleted')
      ->willReturn(FALSE);
    $this->fieldUninstallValidator->expects($this->once())
      ->method('getFieldStoragesByModule')
      ->willReturn([$field_storage]);

    $module = $this->randomMachineName();
    $expected = ['Fields type(s) in use'];
    $reasons = $this->fieldUninstallValidator->validate($module);
    $this->assertSame($expected, $reasons);
  }

}
