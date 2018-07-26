<?php

namespace Drupal\Tests\migrate_drupal\Kernel\d6;

use Drupal\node\Entity\Node;
use Drupal\Tests\node\Kernel\Migrate\d6\MigrateNodeTestBase;

/**
 * Tests follow-up migrations.
 *
 * @group migrate_drupal
 */
class FollowUpMigrationsTest extends MigrateNodeTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_translation',
    'language',
    'menu_ui',
    // A requirement for d6_node_translation.
    'migrate_drupal_multilingual',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigrations([
      'language',
      'd6_language_content_settings',
      'd6_node',
      'd6_node_translation',
    ]);
  }

  /**
   * Test entity reference translations.
   */
  public function testEntityReferenceTranslations() {
    // Test the entity reference field before the follow-up migrations.
    $node = Node::load(10);
    $this->assertSame('13', $node->get('field_reference')->target_id);
    $this->assertSame('13', $node->get('field_reference_2')->target_id);
    $translation = $node->getTranslation('fr');
    $this->assertSame('20', $translation->get('field_reference')->target_id);
    $this->assertSame('20', $translation->get('field_reference_2')->target_id);

    $node = Node::load(12)->getTranslation('en');
    $this->assertSame('10', $node->get('field_reference')->target_id);
    $this->assertSame('10', $node->get('field_reference_2')->target_id);
    $translation = $node->getTranslation('fr');
    $this->assertSame('11', $translation->get('field_reference')->target_id);
    $this->assertSame('11', $translation->get('field_reference_2')->target_id);

    // Run the follow-up migrations.
    $migration_plugin_manager = $this->container->get('plugin.manager.migration');
    $migration_plugin_manager->clearCachedDefinitions();
    $follow_up_migrations = $migration_plugin_manager->createInstances('d6_entity_reference_translation');
    $this->executeMigrations(array_keys($follow_up_migrations));

    // Test the entity reference field after the follow-up migrations.
    $node = Node::load(10);
    $this->assertSame('12', $node->get('field_reference')->target_id);
    $this->assertSame('12', $node->get('field_reference_2')->target_id);
    $translation = $node->getTranslation('fr');
    $this->assertSame('12', $translation->get('field_reference')->target_id);
    $this->assertSame('12', $translation->get('field_reference_2')->target_id);

    $node = Node::load(12)->getTranslation('en');
    $this->assertSame('10', $node->get('field_reference')->target_id);
    $this->assertSame('10', $node->get('field_reference_2')->target_id);
    $translation = $node->getTranslation('fr');
    $this->assertSame('10', $translation->get('field_reference')->target_id);
    $this->assertSame('10', $translation->get('field_reference_2')->target_id);
  }

}
