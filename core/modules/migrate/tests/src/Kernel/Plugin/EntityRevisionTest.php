<?php

namespace Drupal\Tests\migrate\Kernel\Plugin;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

// cspell:ignore tabarnak

/**
 * Tests the EntityRevision destination plugin.
 *
 * @group migrate
 */
class EntityRevisionTest extends MigrateTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'field',
    'filter',
    'language',
    'node',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig('node');
    $this->installSchema('node', ['node_access']);
  }

  /**
   * Tests that EntityRevision correctly handles revision translations.
   */
  public function testRevisionTranslation() {
    ConfigurableLanguage::createFromLangcode('fr')->save();

    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => $this->createContentType()->id(),
      'title' => 'Default 1',
    ]);
    $node->addTranslation('fr', [
      'title' => 'French 1',
    ]);
    $node->save();
    $node->setNewRevision();
    $node->setTitle('Default 2');
    $node->getTranslation('fr')->setTitle('French 2');
    $node->save();

    $migration = [
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          [
            'nid' => $node->id(),
            'vid' => $node->getRevisionId(),
            'langcode' => 'fr',
            'title' => 'Titre nouveau, tabarnak!',
          ],
        ],
        'ids' => [
          'nid' => [
            'type' => 'integer',
          ],
          'vid' => [
            'type' => 'integer',
          ],
          'langcode' => [
            'type' => 'string',
          ],
        ],
      ],
      'process' => [
        'nid' => 'nid',
        'vid' => 'vid',
        'langcode' => 'langcode',
        'title' => 'title',
      ],
      'destination' => [
        'plugin' => 'entity_revision:node',
        'translations' => TRUE,
      ],
    ];

    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->container
      ->get('plugin.manager.migration')
      ->createStubMigration($migration);

    $this->executeMigration($migration);

    // The entity_revision destination uses the revision ID and langcode as its
    // keys (the langcode is only used if the destination is configured for
    // translation), so we should be able to look up the source IDs by revision
    // ID and langcode.
    $source_ids = $migration->getIdMap()->lookupSourceID([
      'vid' => $node->getRevisionId(),
      'langcode' => 'fr',
    ]);
    $this->assertNotEmpty($source_ids);
    $this->assertSame($node->id(), $source_ids['nid']);
    $this->assertSame($node->getRevisionId(), $source_ids['vid']);
    $this->assertSame('fr', $source_ids['langcode']);

    // Confirm the french revision was used in the migration, instead of the
    // default revision.
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::entityTypeManager();
    $revision = $entity_type_manager->getStorage('node')->loadRevision(1);
    $this->assertSame('Default 1', $revision->label());
    $this->assertSame('French 1', $revision->getTranslation('fr')->label());
    $revision = $entity_type_manager->getStorage('node')->loadRevision(2);
    $this->assertSame('Default 2', $revision->label());
    $this->assertSame('Titre nouveau, tabarnak!', $revision->getTranslation('fr')->label());
  }

}
