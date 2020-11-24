<?php

namespace Drupal\Tests\auto_updates\Kernel\ReadinessChecker;

use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager
 *
 * @group auto_updates
 */
class ReadinessCheckerManagerTest extends KernelTestBase {

  use TestCheckerTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['auto_updates', 'auto_updates_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['auto_updates']);
  }

  /**
   * Tests checker messages.
   *
   * @param string[] $error_messages
   *   Test error messages.
   * @param string[] $warning_messages
   *   Test warning messages.
   *
   * @dataProvider providerCheckerMessages
   */
  public function testCheckerMessages(array $error_messages, array $warning_messages): void {
    $this->setTestMessages($error_messages, $warning_messages);
    $manager = $this->container->get('auto_updates.readiness_checker_manager');
    $this->assertSame($warning_messages, $manager->getWarnings());
    $this->assertSame($error_messages, $manager->getErrors());
  }

  /**
   * Data provider for testCheckerMessages().
   *
   * @return string[][]
   *   The test cases for testCheckerMessages().
   */
  public function providerCheckerMessages(): array {
    return [
      'warnings_only' => [
        'errors' => [],
        'warnings' => ['A warning is bad but will not stop an update'],
      ],
      'errors_only' => [
        'errors' => ['A error is very bad and will not stop an update'],
        'warnings' => [],
      ],
      'both' => [
        'errors' => ['A error is very bad and will not stop an update'],
        'warnings' => ['A warning is bad but will not stop an update'],
      ],
      'neither' => [
        'errors' => [],
        'warnings' => [],
      ],
    ];
  }

  /**
   * Test that the option $refresh parameter works.
   */
  public function testMessageRefresh():void {
    $this->setTestMessages(['error1'], ['warning1']);
    $manager = $this->container->get('auto_updates.readiness_checker_manager');
    $this->assertSame(['warning1'], $manager->getWarnings());
    $this->assertSame(['error1'], $manager->getErrors());
    // The readiness checkers will not be invoked in the next calls so changing
    // the test messages will not have an effect.
    $this->setTestMessages(['error2'], ['warning2']);
    $this->assertSame(['warning1'], $manager->getWarnings());
    $this->assertSame(['error1'], $manager->getErrors());
    // Calling get warnings with the optional $refresh parameter will return the
    // new messages.
    $this->assertSame(['warning2'], $manager->getWarnings(TRUE));
    $this->assertSame(['error2'], $manager->getErrors(TRUE));
  }

}
