<?php

namespace Drupal\Tests\auto_updates\Kernel\ReadinessChecker;

use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult;
use Drupal\auto_updates_test\ReadinessChecker\TestChecker1;
use Drupal\auto_updates_test2\ReadinessChecker\TestChecker2;
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
    $this->assertSame($warning_messages, $this->getMessagesFromManager('warnings', TRUE));
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
   * Tests that the manager is run after modules are installed.
   */
  public function testRunOnInstall(): void {
    $this->setTestMessages(['error1'], ['warning1']);
    // Confirm that messages from an existing module are displayed when
    // 'auto_updates' is installed.
    $this->container->get('module_installer')->install(['auto_updates']);
    $this->assertSame(['warning1'], $this->getMessagesFromManager('warnings'));
    $this->assertSame(['error1'], $this->getMessagesFromManager('errors'));

    // Confirm that the checkers are run when a module that provides a readiness
    // checker is installed.
    $this->setTestMessages(['error2'], ['warning2']);
    $this->setTestMessages(['error3'], ['warning3'], 2);
    $this->container->get('module_installer')->install(['auto_updates_test2']);
    $this->assertSame(['warning2', 'warning3'], $this->getMessagesFromManager('warnings'));
    $this->assertSame(['error2', 'error3'], $this->getMessagesFromManager('errors'));

    // Confirm that the checkers are not run when a module that does not provide
    // a readiness checker is installed.
    $this->setTestMessages(['error4'], ['warning4']);
    $this->setTestMessages(['error5'], ['warning5'], 2);
    $this->container->get('module_installer')->install(['help']);
    $this->assertSame(['warning2', 'warning3'], $this->getMessagesFromManager('warnings'));
    $this->assertSame(['error2', 'error3'], $this->getMessagesFromManager('errors'));
  }

  /**
   * Tests that the manager is run after modules are uninstalled.
   */
  public function testRunOnUninstall(): void {
    $this->setTestMessages(['error1'], ['warning1']);
    $this->setTestMessages(['error2'], ['warning2'], 2);
    // Confirm that messages from existing modules are displayed when
    // 'auto_updates' is installed.
    $this->container->get('module_installer')->install(['auto_updates', 'auto_updates_test2', 'help']);
    $this->assertSame(['warning1', 'warning2'], $this->getMessagesFromManager('warnings'));
    $this->assertSame(['error1', 'error2'], $this->getMessagesFromManager('errors'));

    // Confirm that the checkers are run when a module that provides a readiness
    // checker is uninstalled.
    $this->setTestMessages(['error3'], ['warning3']);
    $this->setTestMessages(['error4'], ['warning4'], 2);
    $this->container->get('module_installer')->uninstall(['auto_updates_test2']);
    $this->assertSame(['warning3'], $this->getMessagesFromManager('warnings'));
    $this->assertSame(['error3'], $this->getMessagesFromManager('errors'));

    // Confirm that the checkers are not run when a module that does provide a
    // readiness checker is uninstalled.
    $this->setTestMessages(['error5'], ['warning5']);
    $this->container->get('module_installer')->uninstall(['help']);
    $this->assertSame(['warning3'], $this->getMessagesFromManager('warnings'));
    $this->assertSame(['error3'], $this->getMessagesFromManager('errors'));
  }

  /**
   * @covers ::runIfNeeded
   */
  public function testRunIfNeeded(): void {
    $this->setTestMessages(['error1'], ['warning1']);
    $this->enableModules(['auto_updates']);
    $this->installConfig(['auto_updates']);
    $this->assertSame(['warning1'], $this->getMessagesFromManager('warnings', TRUE));
    $this->assertSame(['error1'], $this->getMessagesFromManager('errors'));
    $this->setTestMessages(['error2'], ['warning2']);
    $manager = $this->container->get('auto_updates.readiness_checker_manager');
    // Confirm that the new message will not be returned because the checkers
    // will not be run.
    $manager->runIfNeeded();
    $this->assertSame(['warning1'], $this->getMessagesFromManager('warnings'));
    $this->assertSame(['error1'], $this->getMessagesFromManager('errors'));

    // Confirm that the new message will not be returned because the checkers
    // will be run if the stored results are deleted.
    /** @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface $key_value */
    $key_value = $this->container->get('keyvalue.expirable')->get('auto_updates');
    $key_value->delete('readiness_check_last_run');
    $manager->runIfNeeded();
    $this->assertSame(['warning2'], $this->getMessagesFromManager('warnings'));
    $this->assertSame(['error2'], $this->getMessagesFromManager('errors'));
  }

  /**
   * Tests the manager when checkers services are changed.
   */
  public function testCheckersServicesAltered(): void {
    $this->setTestMessages(['error1'], ['warning1']);
    // Confirm that messages from existing modules are displayed when
    // 'auto_updates' is installed.
    $this->container->get('module_installer')->install(['auto_updates']);
    $this->assertSame(['warning1'], $this->getMessagesFromManager('warnings'));
    $this->assertSame(['error1'], $this->getMessagesFromManager('errors'));

    $this->setTestMessages(['error2'], ['warning2']);
    // Confirm that messages are still returned after rebuilding the container.
    /** @var \Drush\Drupal\DrupalKernel $kernel */
    $kernel = $this->container->get('kernel');
    $this->container = $kernel->rebuildContainer();
    $this->assertSame(['warning1'], $this->getMessagesFromManager('warnings'));
    $this->assertSame(['error1'], $this->getMessagesFromManager('errors'));

    // Define a constant flag that will cause a duplicate readiness checker
    // service to be defined.
    // @see \Drupal\auto_updates_test\AutoUpdatesTestServiceProvider::alter().
    define('AUTO_UPDATES_DUPLICATE_SERVICE', TRUE);

    // Rebuild the container to trigger the service to be duplicated.
    $kernel = $this->container->get('kernel');
    $this->container = $kernel->rebuildContainer();
    $manager = $this->container->get('auto_updates.readiness_checker_manager');
    // Confirm that after the readiness checkers have been changed previous
    // results will not be returned.
    $this->assertNull($manager->getResults());

    // Confirm that runIfNeeded() will run the checkers if the readiness checker
    // services have changed.
    $manager->runIfNeeded();
    $this->assertSame(['warning2'], $this->getMessagesFromManager('warnings'));
    $this->assertSame(['error2'], $this->getMessagesFromManager('errors'));
  }

  /**
   * Gets the messages of a particular type from the manager.
   *
   * @param string $type
   *   The type of messages to get, either 'warnings' or 'errors'.
   * @param bool $run_checkers
   *   Whether to run the checkers.
   *
   * @return string[]
   *   The messages of the type.
   *
   * @throws \Exception
   */
  protected function getMessagesFromManager(string $type, bool $run_checkers = FALSE): array {
    $this->assertTrue(in_array($type, ['warnings', 'errors']), "Only 'warning' and 'errors' are valid types.");
    $manager = $this->container->get('auto_updates.readiness_checker_manager');
    if ($run_checkers) {
      $manager->run();
    }
    $results = $manager->getResults();
    $messages = [];
    if ($results) {
      foreach ($results as $result) {
        $messages = array_merge($messages, $type === 'warnings' ? $result->getWarningMessages() : $result->getErrorMessages());
      }
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
   * @param int $checker_number
   *   The test checker to use, either 1 or 2. Defaults to 1.
   */
  private function setTestMessages(array $error_messages, array $warning_messages, int $checker_number = 1): void {
    $test_checker = $this->createMock(TestChecker1::class);
    $test_checker->_serviceId = "auto_updates_test_$checker_number.checker";
    $result = new ReadinessCheckerResult($test_checker, NULL, $error_messages, NULL, $warning_messages);
    $checker_number === 1 ? TestChecker1::setTestResult($result) : TestChecker2::setTestResult($result);
  }

}
