<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\field\FieldUninstallValidator
 * @group field
 */
class FieldUninstallValidatorTest extends UnitTestCase {

  /**
   * The mock field uninstall validator.
   *
   * @var \Drupal\field\FieldUninstallValidator|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fieldUninstallValidator;

  /**
   * The mock field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fieldTypePluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fieldUninstallValidator = $this->getMockBuilder('Drupal\field\FieldUninstallValidator')
      ->disableOriginalConstructor()
      ->onlyMethods(['getFieldStoragesByModule', 'getFieldTypeLabel'])
      ->getMock();
    $this->fieldUninstallValidator->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * @covers ::validate
   */
  public function testValidateNoStorages(): void {
    $this->fieldUninstallValidator->expects($this->once())
      ->method('getFieldStoragesByModule')
      ->willReturn([]);

    $module = $this->randomMachineName();
    $expected = [];
    $reasons = $this->fieldUninstallValidator->validate($module);
    $this->assertEquals($expected, $reasons);
  }

  /**
   * @covers ::validate
   */
  public function testValidateDeleted(): void {
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
    $this->assertEquals($expected, $reasons);
  }

  /**
   * @covers ::validate
   */
  public function testValidateNoDeleted(): void {
    $field_storage = $this->getMockBuilder('Drupal\field\Entity\FieldStorageConfig')
      ->disableOriginalConstructor()
      ->getMock();
    $field_storage->expects($this->once())
      ->method('isDeleted')
      ->willReturn(FALSE);
    $field_type = $this->randomMachineName();
    $field_storage->expects($this->once())
      ->method('getType')
      ->willReturn($field_type);
    $field_name = $this->randomMachineName();
    $field_storage->expects($this->once())
      ->method('getLabel')
      ->willReturn($field_name);
    $this->fieldUninstallValidator->expects($this->once())
      ->method('getFieldStoragesByModule')
      ->willReturn([$field_storage]);
    $field_type_label = $this->randomMachineName();
    $this->fieldUninstallValidator->expects($this->once())
      ->method('getFieldTypeLabel')
      ->willReturn($field_type_label);

    $module = $this->randomMachineName();
    $expected = ["The <em class=\"placeholder\">$field_type_label</em> field type is used in the following field: $field_name"];
    $reasons = $this->fieldUninstallValidator->validate($module);
    $this->assertEquals($expected, $reasons);
  }

}
