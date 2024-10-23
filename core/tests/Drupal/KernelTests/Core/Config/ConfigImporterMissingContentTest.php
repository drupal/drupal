<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Block\Plugin\Block\Broken;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Psr\Log\LoggerInterface;

/**
 * Tests importing configuration which has missing content dependencies.
 *
 * @group config
 */
class ConfigImporterMissingContentTest extends KernelTestBase implements LoggerInterface {
  use BlockCreationTrait;
  use RfcLoggerTrait;

  /**
   * The logged messages.
   *
   * @var string[]
   */
  protected $logMessages = [];

  /**
   * Config Importer object used for testing.
   *
   * @var \Drupal\Core\Config\ConfigImporter
   */
  protected $configImporter;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'entity_test',
    'config_test',
    'config_import_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->register('logger.ConfigImporterMissingContentTest', __CLASS__)->addTag('logger');
    $container->set('logger.ConfigImporterMissingContentTest', $this);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installConfig(['system', 'config_test']);
    // Installing config_test's default configuration pollutes the global
    // variable being used for recording hook invocations by this test already,
    // so it has to be cleared out manually.
    unset($GLOBALS['hook_config_test']);

    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));

    // Set up the ConfigImporter object for testing.
    $storage_comparer = new StorageComparer(
      $this->container->get('config.storage.sync'),
      $this->container->get('config.storage')
    );
    $this->configImporter = new ConfigImporter(
      $storage_comparer->createChangelist(),
      $this->container->get('event_dispatcher'),
      $this->container->get('config.manager'),
      $this->container->get('lock'),
      $this->container->get('config.typed'),
      $this->container->get('module_handler'),
      $this->container->get('module_installer'),
      $this->container->get('theme_handler'),
      $this->container->get('string_translation'),
      $this->container->get('extension.list.module'),
      $this->container->get('extension.list.theme')
    );
  }

  /**
   * Tests the missing content event is fired.
   *
   * @see \Drupal\Core\Config\ConfigImporter::processMissingContent()
   * @see \Drupal\config_import_test\EventSubscriber
   */
  public function testMissingContent(): void {
    \Drupal::state()->set('config_import_test.config_import_missing_content', TRUE);

    // Update a configuration entity in the sync directory to have a dependency
    // on two content entities that do not exist.
    $storage = $this->container->get('config.storage');
    $sync = $this->container->get('config.storage.sync');
    $entity_one = EntityTest::create(['name' => 'one']);
    $entity_two = EntityTest::create(['name' => 'two']);
    $entity_three = EntityTest::create(['name' => 'three']);
    $dynamic_name = 'config_test.dynamic.dotted.default';
    $original_dynamic_data = $storage->read($dynamic_name);
    // Entity one will be resolved by
    // \Drupal\config_import_test\EventSubscriber::onConfigImporterMissingContentOne().
    $original_dynamic_data['dependencies']['content'][] = $entity_one->getConfigDependencyName();
    // Entity two will be resolved by
    // \Drupal\config_import_test\EventSubscriber::onConfigImporterMissingContentTwo().
    $original_dynamic_data['dependencies']['content'][] = $entity_two->getConfigDependencyName();
    // Entity three will be resolved by
    // \Drupal\Core\Config\Importer\FinalMissingContentSubscriber.
    $original_dynamic_data['dependencies']['content'][] = $entity_three->getConfigDependencyName();
    $sync->write($dynamic_name, $original_dynamic_data);

    // Import.
    $this->configImporter->reset()->import();
    $this->assertEquals([], $this->configImporter->getErrors(), 'There were no errors during the import.');
    $this->assertEquals($entity_one->uuid(), \Drupal::state()->get('config_import_test.config_import_missing_content_one'), 'The missing content event is fired during configuration import.');
    $this->assertEquals($entity_two->uuid(), \Drupal::state()->get('config_import_test.config_import_missing_content_two'), 'The missing content event is fired during configuration import.');
    $original_dynamic_data = $storage->read($dynamic_name);
    $this->assertEquals([$entity_one->getConfigDependencyName(), $entity_two->getConfigDependencyName(), $entity_three->getConfigDependencyName()], $original_dynamic_data['dependencies']['content'], 'The imported configuration entity has the missing content entity dependency.');
  }

  /**
   * Tests the missing content, config import and the block plugin manager.
   *
   * @see \Drupal\Core\Config\ConfigImporter::processMissingContent()
   * @see \Drupal\config_import_test\EventSubscriber
   */
  public function testMissingBlockContent(): void {
    $this->enableModules([
      'block',
      'block_content',
      'field',
      'text',
    ]);
    $this->container->get('theme_installer')->install(['stark']);
    $this->installEntitySchema('block_content');
    $this->installConfig(['block_content']);
    // Create a block content type.
    $block_content_type = BlockContentType::create([
      'id' => 'test',
      'label' => 'Test block content',
      'description' => "Provides a block type",
    ]);
    $block_content_type->save();
    // And a block content entity.
    $block_content = BlockContent::create([
      'info' => 'Prototype',
      'type' => 'test',
      // Set the UUID to make asserting against missing test easy.
      'uuid' => '6376f337-fcbf-4b28-b30e-ed5b6932e692',
    ]);
    $block_content->save();
    $plugin_id = 'block_content' . PluginBase::DERIVATIVE_SEPARATOR . $block_content->uuid();
    $block = $this->placeBlock($plugin_id);

    $storage = $this->container->get('config.storage');
    $sync = $this->container->get('config.storage.sync');

    $this->copyConfig($storage, $sync);

    $block->delete();
    $block_content->delete();
    $block_content_type->delete();

    // Import.
    $this->logMessages = [];
    $config_importer = $this->configImporter();
    $config_importer->import();
    $this->assertNotContains('The "block_content:6376f337-fcbf-4b28-b30e-ed5b6932e692" block plugin was not found', $this->logMessages);

    // Ensure the expected message is generated when creating an instance of the
    // block.
    $instance = $this->container->get('plugin.manager.block')->createInstance($plugin_id);
    $this->assertContains('The "block_content:6376f337-fcbf-4b28-b30e-ed5b6932e692" block plugin was not found', $this->logMessages);
    $this->assertInstanceOf(Broken::class, $instance);
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []): void {
    $this->logMessages[] = PlainTextOutput::renderFromHtml(new FormattableMarkup($message, $context));
  }

}
