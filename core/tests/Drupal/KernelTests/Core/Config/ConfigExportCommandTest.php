<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Command\Exception\UserAbortException;
use Drupal\Core\Config\Command\ConfigExportCommand;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\SyncFactory;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

// cspell:ignore arrr

/**
 * Tests the 'config:export' command.
 *
 * This command performs the same configuration export a user can trigger
 * from the Configuration module's user interface, except it writes the
 * result directly into the sync directory rather than a downloaded archive.
 *
 * @see \Drupal\Tests\config\Functional\ConfigExportUITest
 */
#[Group('config')]
#[RunTestsInSeparateProcesses]
#[CoversClass(ConfigExportCommand::class)]
class ConfigExportCommandTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'config_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'config_test']);
    $this->config('system.site')
      ->set('name', 'dr config')
      ->set('slogan', 'dr config for export')
      ->save();

    // Set up an override.
    // @see \Drupal\Tests\config\Functional\ConfigExportUITest::testExport()
    $GLOBALS['config']['system.maintenance']['message'] = 'Foo';
  }

  /**
   * Builds a command tester for the 'config:export' command.
   */
  private function commandTester(?Settings $settings = NULL): CommandTester {
    // The SyncFactory service is private, so build it by hand.
    $sync_factory = new SyncFactory(
      $this->container->getParameter('app.root'),
      $settings ?? Settings::getInstance(),
      $this->container->get('config.storage'),
      $this->container->get('extension.list.module'),
      $this->container->get('extension.list.theme'),
      $this->container->get('config.parsing_cache'),
    );
    $command = new ConfigExportCommand($this->container->get('config.storage.export'), $sync_factory);
    return new CommandTester($command);
  }

  /**
   * Tests the command fails cleanly when no sync directory is defined.
   */
  public function testUndefinedSyncDirectory(): void {
    $settings = Settings::getAll();
    unset($settings['config_sync_directory']);
    $tester = $this->commandTester(new Settings($settings));
    self::assertSame(Command::FAILURE, $tester->execute(['--yes' => TRUE]));
    self::assertStringContainsString('config sync directory is not defined', $tester->getDisplay());
  }

  /**
   * Tests export of configuration.
   */
  public function testExportCommand(): void {
    $active = $this->container->get('config.storage');
    $sync = $this->container->get('config.storage.sync');

    // Ensure the override is in effect.
    self::assertSame('Foo', \Drupal::config('system.maintenance')->get('message'));
    // Before the first export the config is the same.
    self::assertEmpty($sync->listAll());

    // Execute it with --yes.
    $tester = $this->commandTester();
    $code = $tester->execute(['--yes' => TRUE]);
    $display = $tester->getDisplay();

    self::assertSame(Command::SUCCESS, $code);
    self::assertStringContainsString('config_test.dynamic.dotted.default', $display);
    self::assertStringContainsString('Configuration successfully exported', $display);
    // Ensure the override was not exported.
    $this->assertNotSame('Foo', $sync->read('system.maintenance')['message']);
    // The sync storage and the active are now the same.
    self::assertStorageEquals($active, $sync);

    // Run the command again. We do not expect a confirmation question.
    $tester = $this->commandTester();
    $code = $tester->execute([]);
    self::assertSame(Command::SUCCESS, $code);
    // Nothing changed, normalize tester output because the string is long.
    $display = preg_replace('/\s+/', ' ', $tester->getDisplay(TRUE));
    self::assertStringContainsString('The active configuration to export matches the configuration in the sync directory.', $display);

    // Back up the sync storage.
    $expected = new MemoryStorage();
    $this->copyConfig($sync, $expected);

    // Install the transformation test module.
    $this->enableModules(['config_transformer_test']);
    $this->config('system.site')
      ->set('name', 'dr transformed')
      ->set('slogan', 'dr not transformed for pirates')
      ->save();
    // Reset the export storage so that it does a transformation again.
    (function () {
      unset($this->storage);

    })->call($this->container->get('config.storage.export'));

    // Execute the command but cancel the export.
    $tester = $this->commandTester();
    $tester->setInputs(['no']);
    try {
      $tester->execute([], ['interactive' => TRUE]);
      $this->fail('Expected a UserAbortException to be thrown.');
    }
    catch (UserAbortException) {
      // Expected.
    }
    // The sync storage remains intact.
    self::assertStorageEquals($expected, $sync);

    // Execute the command and answer yes.
    $tester = $this->commandTester();
    $tester->setInputs(['yes']);
    $code = $tester->execute([], ['interactive' => TRUE]);

    self::assertSame(Command::SUCCESS, $code);

    // The test transformer transforms the slogan on export.
    $exported_site = $sync->read('system.site');
    self::assertSame('dr transformed', $exported_site['name']);
    self::assertSame('dr config for export Arrr', $exported_site['slogan']);
  }

  /**
   * Asserts that two config storage objects have the same content.
   *
   * @param \Drupal\Core\Config\StorageInterface $expected
   *   The storage with the expected data.
   * @param \Drupal\Core\Config\StorageInterface $actual
   *   The storage with the actual data.
   * @param string $message
   *   The message to add to the assertion.
   */
  protected static function assertStorageEquals(StorageInterface $expected, StorageInterface $actual, string $message = ''): void {
    // The same collections have to exist.
    static::assertEqualsCanonicalizing($expected->getAllCollectionNames(), $actual->getAllCollectionNames(), $message);
    // Now loop over all collections and assert the data to be equal.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $expected->getAllCollectionNames()) as $collection) {
      $expected_collection = $expected->createCollection($collection);
      $actual_collection = $actual->createCollection($collection);
      // The same names are present in both.
      static::assertEqualsCanonicalizing($expected_collection->listAll(), $actual_collection->listAll(), $message);
      foreach ($expected_collection->listAll() as $name) {
        // The same data can be read from both.
        static::assertEquals($expected_collection->read($name), $actual_collection->read($name), $message . ' ' . $name);
      }
    }
  }

}
