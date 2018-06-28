<?php

namespace Drupal\Tests\migrate_drupal\Kernel\d7;

use Drupal\node\Entity\Node;
use Drupal\Tests\file\Kernel\Migrate\d7\FileMigrationSetupTrait;

/**
 * Tests follow-up migrations.
 *
 * @group migrate_drupal
 */
class FollowUpMigrationsTest extends MigrateDrupal7TestBase {

  use FileMigrationSetupTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_translation',
    'comment',
    'datetime',
    'file',
    'image',
    'language',
    'link',
    'menu_ui',
    'node',
    'taxonomy',
    'telephone',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->fileMigrationSetup();

    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(static::$modules);
    $this->installSchema('node', ['node_access']);

    $this->executeMigrations([
      'language',
      'd7_user_role',
      'd7_user',
      'd7_node_type',
      'd7_language_content_settings',
      'd7_comment_type',
      'd7_taxonomy_vocabulary',
      'd7_field',
      'd7_field_instance',
      'd7_node',
      'd7_node_translation',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFileMigrationInfo() {
    return [
      'path' => 'public://sites/default/files/cube.jpeg',
      'size' => '3620',
      'base_path' => 'public://',
      'plugin_id' => 'd7_file',
    ];
  }

  /**
   * Test entity reference translations.
   */
  public function testEntityReferenceTranslations() {
    // Test the entity reference field before the follow-up migrations.
    $node = Node::load(2);
    $this->assertSame('5', $node->get('field_reference')->target_id);
    $this->assertSame('5', $node->get('field_reference_2')->target_id);
    $translation = $node->getTranslation('is');
    $this->assertSame('4', $translation->get('field_reference')->target_id);
    $this->assertSame('4', $translation->get('field_reference_2')->target_id);

    $node = Node::load(4);
    $this->assertSame('3', $node->get('field_reference')->target_id);
    $this->assertSame('3', $node->get('field_reference_2')->target_id);
    $translation = $node->getTranslation('en');
    $this->assertSame('2', $translation->get('field_reference')->target_id);
    $this->assertSame('2', $translation->get('field_reference_2')->target_id);

    // Run the follow-up migrations.
    $migration_plugin_manager = $this->container->get('plugin.manager.migration');
    $migration_plugin_manager->clearCachedDefinitions();
    $follow_up_migrations = $migration_plugin_manager->createInstances('d7_entity_reference_translation');
    $this->executeMigrations(array_keys($follow_up_migrations));

    // Test the entity reference field after the follow-up migrations.
    $node = Node::load(2);
    $this->assertSame('4', $node->get('field_reference')->target_id);
    $this->assertSame('4', $node->get('field_reference_2')->target_id);
    $translation = $node->getTranslation('is');
    $this->assertSame('4', $translation->get('field_reference')->target_id);
    $this->assertSame('4', $translation->get('field_reference_2')->target_id);

    $node = Node::load(4);
    $this->assertSame('2', $node->get('field_reference')->target_id);
    $this->assertSame('2', $node->get('field_reference_2')->target_id);
    $translation = $node->getTranslation('en');
    $this->assertSame('2', $translation->get('field_reference')->target_id);
    $this->assertSame('2', $translation->get('field_reference_2')->target_id);
  }

}
