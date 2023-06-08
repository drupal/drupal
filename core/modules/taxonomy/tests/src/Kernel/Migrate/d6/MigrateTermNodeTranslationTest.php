<?php

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\node\Entity\Node;

/**
 * Upgrade taxonomy term node associations.
 *
 * @group migrate_drupal_6
 */
class MigrateTermNodeTranslationTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'content_translation',
    'language',
    'locale',
    'menu_ui',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installConfig(['node']);
    $this->installSchema('node', ['node_access']);

    $this->executeMigration('language');
    $this->executeMigration('d6_node_settings');
    $this->migrateUsers(FALSE);
    $this->migrateFields();
    $this->migrateTaxonomy();
    $this->migrateContent(['translations']);

    // This is a base plugin id and we want to run all derivatives.
    $this->executeMigrations([
      'd6_term_node',
      'd6_term_node_translation',
    ]);
  }

  /**
   * Tests the Drupal 6 term-node association to Drupal 8 migration.
   */
  public function testTermNode() {
    $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->resetCache([18, 21]);

    // Test with translated content type employee. Vocabulary
    // field_vocabulary_name_much_longe is a localized vocabulary and
    // field_vocabulary_3_i_2_ is a per language vocabulary.
    // An untranslated node.
    $node = Node::load(18);
    // A localized vocabulary.
    $this->assertSame('15', $node->field_vocabulary_name_much_longe[0]->target_id);
    // Per language vocabulary.
    $this->assertSame('5', $node->field_vocabulary_3_i_2_[0]->target_id);

    // A translated node.
    // The English node.
    $node = Node::load(21);
    $this->assertSame('15', $node->field_vocabulary_name_much_longe[0]->target_id);
    $this->assertSame('4', $node->field_vocabulary_3_i_2_[0]->target_id);
    // The French translation of the English node.
    $translation = $node->getTranslation('fr');
    $this->assertSame('14', $translation->field_vocabulary_name_much_longe[0]->target_id);
    $this->assertSame('9', $translation->field_vocabulary_3_i_2_[0]->target_id);
  }

}
