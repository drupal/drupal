<?php

declare(strict_types=1);

namespace Drupal\Tests\content_translation\Kernel;

use Drupal\Core\Config\ConfigImporterFactory;
use Drupal\Core\Config\StorageComparer;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests content translation updates performed during config import.
 */
#[Group('content_translation')]
#[RunTestsInSeparateProcesses]
class ContentTranslationConfigImportTest extends KernelTestBase {

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
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system']);
    $this->installEntitySchema('entity_test_mul');
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));

    // Set up the ConfigImporter object for testing.
    $storage_comparer = new StorageComparer(
      $this->container->get('config.storage.sync'),
      $this->container->get('config.storage')
    );
    $this->configImporter = $this->container->get(ConfigImporterFactory::class)->get($storage_comparer->createChangelist());
  }

  /**
   * Tests config import updates.
   */
  public function testConfigImportUpdates(): void {
    $entity_type_id = 'entity_test_mul';
    $config_id = $entity_type_id . '.' . $entity_type_id;
    $config_name = 'language.content_settings.' . $config_id;
    $storage = $this->container->get('config.storage');
    $sync = $this->container->get('config.storage.sync');

    // Verify the configuration to create does not exist yet.
    $this->assertFalse($storage->exists($config_name), $config_name . ' not found.');

    // Create new config entity.
    $data = [
      'uuid' => 'a019d89b-c4d9-4ed4-b859-894e4e2e93cf',
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [
        'module' => ['content_translation'],
      ],
      'id' => $config_id,
      'target_entity_type_id' => 'entity_test_mul',
      'target_bundle' => 'entity_test_mul',
      'default_langcode' => 'site_default',
      'language_alterable' => FALSE,
      'third_party_settings' => [
        'content_translation' => ['enabled' => TRUE],
      ],
    ];
    $sync->write($config_name, $data);
    $this->assertTrue($sync->exists($config_name), $config_name . ' found.');

    // Import.
    $this->configImporter->reset()->import();

    // Verify the values appeared.
    $config = $this->config($config_name);
    $this->assertSame($config_id, $config->get('id'));

    // Verify that updates were performed.
    $entity_type = $this->container->get('entity_type.manager')->getDefinition($entity_type_id);
    $table = $entity_type->getDataTable();
    $db_schema = $this->container->get('database')->schema();
    $result = $db_schema->fieldExists($table, 'content_translation_source') && $db_schema->fieldExists($table, 'content_translation_outdated');
    $this->assertTrue($result, 'Content translation updates were successfully performed during config import.');
  }

}
