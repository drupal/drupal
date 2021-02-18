<?php

namespace Drupal\Tests\auto_updates\Kernel\ReadinessChecker;

use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult;
use Drupal\auto_updates_test\ReadinessChecker\TestChecker;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager
 *
 * @group auto_updates
 */
class ReadinessCheckerManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['auto_updates_test'];

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
    $this->enableModules(['auto_updates']);
    $this->installConfig(['auto_updates']);
    $this->assertSame($warning_messages, $this->getMessagesFromManager('warnings'));
    $this->assertSame($error_messages, $this->getMessagesFromManager('errors'));
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
        'warnings' => ['A warning is bad but will NOT stop an update'],
      ],
      'errors_only' => [
        'errors' => ['A error is very bad and WILL stop an update'],
        'warnings' => [],
      ],
      'both' => [
        'errors' => ['A error is very bad and WILL stop an update'],
        'warnings' => ['A warning is bad but will NOT stop an update'],
      ],
      'neither' => [
        'errors' => [],
        'warnings' => [],
      ],
    ];
  }

  /**
   * Tests that the manager is run after the auto_updates module is installed.
   */
  public function testRunOnInstall(): void {
    $key_value = $this->container->get('keyvalue.expirable')->get('auto_updates');
    $this->assertEmpty($key_value->get('readiness_check_last_run'));
    $this->setTestMessages(['error1'], ['warning1']);
    $this->container->get('module_installer')->install(['auto_updates']);
    /** @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value */
    $last_run = $key_value->get('readiness_check_last_run');
    $this->assertNotEmpty($last_run);
    $this->assertCount(1, $last_run['results']);
  }

  /**
   * Test that the option $refresh parameter works.
   */
  public function testMessageRefresh(): void {
    $this->setTestMessages(['error1'], ['warning1']);
    $this->enableModules(['auto_updates']);
    $this->installConfig(['auto_updates']);
    $this->assertSame(['warning1'], $this->getMessagesFromManager('warnings'));
    $this->assertSame(['error1'], $this->getMessagesFromManager('errors'));
    // The readiness checkers will not be invoked in the next calls so changing
    // the test messages will not have an effect.
    $this->setTestMessages(['error2'], ['warning2']);
    $this->assertSame(['warning1'], $this->getMessagesFromManager('warnings'));
    $this->assertSame(['error1'], $this->getMessagesFromManager('errors'));
    // Calling get warnings with the optional $refresh parameter will return the
    // new messages.
    $this->assertSame(['warning2'], $this->getMessagesFromManager('warnings', TRUE));
    $this->assertSame(['error2'], $this->getMessagesFromManager('errors'));

    // Assert that empty results are also cached.
    $this->setTestMessages([], []);
    $this->assertSame([], $this->getMessagesFromManager('warnings', TRUE));
    $this->assertSame([], $this->getMessagesFromManager('errors'));
    $this->setTestMessages(['error3'], ['warning3']);
    $this->assertSame([], $this->getMessagesFromManager('warnings'));
    $this->assertSame([], $this->getMessagesFromManager('errors'));
    // Calling get warnings with the optional $refresh parameter will return the
    // new messages.
    $this->assertSame(['warning3'], $this->getMessagesFromManager('warnings', TRUE));
    $this->assertSame(['error3'], $this->getMessagesFromManager('errors'));
  }

  /**
   * Gets the messages of a particular type from the manager.
   *
   * @param string $type
   *   The type of messages to get, either 'warnings' or 'errors'.
   * @param bool $refresh
   *   Whether to refresh the results.
   *
   * @return string[]
   *   The messages of the type.
   *
   * @throws \Exception
   */
  protected function getMessagesFromManager(string $type, bool $refresh = FALSE): array {
    $this->assertTrue(in_array($type, ['warnings', 'errors']), "Only 'warning' and 'errors' are valid types.");
    $manager = $this->container->get('auto_updates.readiness_checker_manager');
    $results = $manager->getResults($refresh);
    $messages = [];
    foreach ($results as $result) {
      $messages = array_merge($messages, $type === 'warnings' ? $result->getWarningMessages() : $result->getErrorMessages());
    }
    return $messages;
  }

  /**
   * Sets the test messages.
   *
   * @param string[] $error_messages
   *   The test error messages.
   * @param string[] $warning_messages
   *   The test warning messages.
   */
  private function setTestMessages(array $error_messages, array $warning_messages): void {
    TestChecker::setTestResult(
      new ReadinessCheckerResult(
      $this->container->get('auto_updates_test.checker'),
      NULL,
      $error_messages,
      NULL,
      $warning_messages
      )
    );
  }

}
