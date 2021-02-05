<?php

namespace Drupal\Tests\auto_updates\Kernel\ReadinessChecker;

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
    TestChecker::setTestMessages($error_messages, $warning_messages);
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
   * Test that the option $refresh parameter works.
   */
  public function testMessageRefresh():void {
    TestChecker::setTestMessages(['error1'], ['warning1']);
    $this->assertSame(['warning1'], $this->getMessagesFromManager('warnings'));
    $this->assertSame(['error1'], $this->getMessagesFromManager('errors'));
    // The readiness checkers will not be invoked in the next calls so changing
    // the test messages will not have an effect.
    TestChecker::setTestMessages(['error2'], ['warning2']);
    $this->assertSame(['warning1'], $this->getMessagesFromManager('warnings'));
    $this->assertSame(['error1'], $this->getMessagesFromManager('errors'));
    // Calling get warnings with the optional $refresh parameter will return the
    // new messages.
    $this->assertSame(['warning2'], $this->getMessagesFromManager('warnings', TRUE));
    $this->assertSame(['error2'], $this->getMessagesFromManager('errors'));

    // Assert that empty results are also cached.
    TestChecker::setTestMessages([], []);
    $this->assertSame([], $this->getMessagesFromManager('warnings', TRUE));
    $this->assertSame([], $this->getMessagesFromManager('errors'));
    TestChecker::setTestMessages(['error3'], ['warning3']);
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

}
