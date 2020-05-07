<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\migrate\MigrateExecutable;
use Drupal\node\Entity\NodeType;

/**
 * Tests migrating non-Drupal translated content.
 *
 * Ensure it's possible to migrate in translations, even if there's no nid or
 * tnid property on the source.
 *
 * @group migrate
 */
class MigrateExternalTranslatedTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'language',
    'node',
    'field',
    'migrate_external_translated_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    // Create some languages.
    ConfigurableLanguage::createFromLangcode('en')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Create a content type.
    NodeType::create([
      'type' => 'external_test',
      'name' => 'Test node type',
    ])->save();
  }

  /**
   * Test importing and rolling back our data.
   */
  public function testMigrations() {
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $this->assertCount(0, $storage->loadMultiple());

    // Run the migrations.
    $migration_ids = ['external_translated_test_node', 'external_translated_test_node_translation'];
    $this->executeMigrations($migration_ids);
    $this->assertCount(3, $storage->loadMultiple());

    $node = $storage->load(1);
    $this->assertEquals('en', $node->language()->getId());
    $this->assertEquals('Cat', $node->title->value);
    $this->assertEquals('Chat', $node->getTranslation('fr')->title->value);
    $this->assertEquals('Gato', $node->getTranslation('es')->title->value);

    $node = $storage->load(2);
    $this->assertEquals('en', $node->language()->getId());
    $this->assertEquals('Dog', $node->title->value);
    $this->assertEquals('Chien', $node->getTranslation('fr')->title->value);
    $this->assertFalse($node->hasTranslation('es'), "No spanish translation for node 2");

    $node = $storage->load(3);
    $this->assertEquals('en', $node->language()->getId());
    $this->assertEquals('Monkey', $node->title->value);
    $this->assertFalse($node->hasTranslation('fr'), "No french translation for node 3");
    $this->assertFalse($node->hasTranslation('es'), "No spanish translation for node 3");

    $this->assertNull($storage->load(4), "No node 4 migrated");

    // Roll back the migrations.
    foreach ($migration_ids as $migration_id) {
      $migration = $this->getMigration($migration_id);
      $executable = new MigrateExecutable($migration, $this);
      $executable->rollback();
    }

    $this->assertCount(0, $storage->loadMultiple());
  }

}
