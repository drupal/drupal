<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Extension;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Extension\RequiredModuleUninstallValidator
 * @group Extension
 */
class RequiredModuleUninstallValidatorTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\Extension\RequiredModuleUninstallValidator|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $uninstallValidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->uninstallValidator = $this->getMockBuilder('Drupal\Core\Extension\RequiredModuleUninstallValidator')
      ->disableOriginalConstructor()
      ->onlyMethods(['getModuleInfoByModule'])
      ->getMock();
    $this->uninstallValidator->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * @covers ::validate
   */
  public function testValidateNoModule(): void {
    $this->uninstallValidator->expects($this->once())
      ->method('getModuleInfoByModule')
      ->willReturn([]);

    $module = $this->randomMachineName();
    $expected = [];
    $reasons = $this->uninstallValidator->validate($module);
    $this->assertSame($expected, $reasons);
  }

  /**
   * @covers ::validate
   */
  public function testValidateNotRequired(): void {
    $module = $this->randomMachineName();

    $this->uninstallValidator->expects($this->once())
      ->method('getModuleInfoByModule')
      ->willReturn(['required' => FALSE, 'name' => $module]);

    $expected = [];
    $reasons = $this->uninstallValidator->validate($module);
    $this->assertSame($expected, $reasons);
  }

  /**
   * @covers ::validate
   */
  public function testValidateRequired(): void {
    $module = $this->randomMachineName();

    $this->uninstallValidator->expects($this->once())
      ->method('getModuleInfoByModule')
      ->willReturn(['required' => TRUE, 'name' => $module]);

    $expected = ["The $module module is required"];
    $reasons = $this->uninstallValidator->validate($module);
    $this->assertEquals($expected, $reasons);
  }

}
