<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel;

use Drupal\Core\Database\Database;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests node storage loads fields of mixed cardinality and translatability.
 */
#[Group('node')]
#[RunTestsInSeparateProcesses]
class NodeStorageLoadFieldDataTest extends KernelTestBase {

  use NodeCreationTrait;
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'language',
    'node',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('user', 'users_data');
    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');
    $this->createContentType([
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ], FALSE);
    ConfigurableLanguage::createFromLangcode('en')
      ->save();
  }

  /**
   * Tests fields load correctly.
   */
  public function testLoadFieldData(): void {
    $fields = [
      'field_single_translatable',
      'field_single_untranslatable',
      'field_mul_translatable',
      'field_mul_untranslatable',
    ];
    foreach ($fields as $field) {
      $fieldStorage = FieldStorageConfig::create([
        'field_name' => $field,
        'entity_type' => 'node',
        'type' => 'string',
        'cardinality' => str_contains($field, 'single') ? 1 : FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
        'persist_with_no_fields' => TRUE,
      ]);
      $fieldStorage->save();

      FieldConfig::create([
        'field_storage' => $fieldStorage,
        'bundle' => 'page',
        'translatable' => !str_contains($field, 'untranslatable'),
      ])->save();
    }

    // Create a node in a non-default language. When the configurable language
    // is deleted, the row for the node in the revision table will be set to
    // 'und'. Confirm that data for fields on a node in that state still populate
    // correctly on entity load.
    // @see NodeEntityHooks::configurableLanguageDelete()
    // @see NodeStorage::clearRevisionsLanguage()
    $esLanguage = ConfigurableLanguage::createFromLangcode('es');
    $esLanguage->save();
    $this->assertSame('en', \Drupal::languageManager()->getDefaultLanguage()->getId());
    $esValues = [
      'field_single_translatable' => ['(es) Single translatable'],
      'field_single_untranslatable' => ['(es) Single untranslatable'],
      'field_mul_translatable' => [
        '(es) Multiple translatable 1',
        '(es) Multiple translatable 2',
      ],
      'field_mul_untranslatable' => [
        '(es) Multiple untranslatable 1',
        '(es) Multiple untranslatable 2',
      ],
    ];
    $esId = $this->createNode($esValues + ['langcode' => 'es'])->id();
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $storage->resetCache();
    $esNode = $storage->load($esId);
    $storedValues = $esNode->toArray();
    foreach ($esValues as $fieldName => $fieldValue) {
      $this->assertSame($fieldValue, array_column($storedValues[$fieldName], 'value'));
    }
    $esLanguage->delete();
    $langcodes = \Drupal::database()->select($storage->getRevisionTable(), 'node_revision')
      ->condition('nid', $esId)
      ->fields('node_revision', ['langcode'])
      ->execute()
      ->fetchCol();
    $this->assertSame(['und'], $langcodes);
    $storage->resetCache();
    $esNode = $storage->load($esId);
    $storedValues = $esNode->toArray();
    foreach ($esValues as $fieldName => $fieldValue) {
      $this->assertSame($fieldValue, array_column($storedValues[$fieldName], 'value'));
    }

    // Create another node in the default language ('en'). When the language
    // module is uninstalled, the 'en' configurable language will be deleted.
    // Since 'en' is the site default language, the entries for the node in the
    // revision table will keep the langcode as 'en'.
    $values = [
      'field_single_translatable' => ['Single translatable'],
      'field_single_untranslatable' => ['Single untranslatable'],
      'field_mul_translatable' => [
        'Multiple translatable 1',
        'Multiple translatable 2',
      ],
      'field_mul_untranslatable' => [
        'Multiple untranslatable 1',
        'Multiple untranslatable 2',
      ],
    ];
    $id = $this->createNode($values + ['langcode' => 'en'])->id();
    $storage->resetCache();
    $node = $storage->load($id);
    $storedValues = $node->toArray();
    foreach ($values as $fieldName => $fieldValue) {
      $this->assertSame($fieldValue, array_column($storedValues[$fieldName], 'value'));
    }

    \Drupal::service(ModuleInstallerInterface::class)->uninstall(['language']);
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $langcodes = Database::getConnection()->select($storage->getRevisionTable(), 'node_revision')
      ->condition('nid', $id)
      ->fields('node_revision', ['langcode'])
      ->execute()
      ->fetchCol();
    $this->assertSame(['en'], $langcodes);
    $storage->resetCache();
    $node = $storage->load($id);
    $storedValues = $node->toArray();
    foreach ($values as $fieldName => $fieldValue) {
      $this->assertSame($fieldValue, array_column($storedValues[$fieldName], 'value'));
    }
  }

}
