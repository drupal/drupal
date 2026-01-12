<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Extension;

use Drupal\Core\Extension\RequiredModuleUninstallValidator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Extension\RequiredModuleUninstallValidator.
 */
#[CoversClass(RequiredModuleUninstallValidator::class)]
#[Group('Extension')]
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
   * Tests validate no module.
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
   * Tests validate not required.
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
   * Tests validate required.
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
