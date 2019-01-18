<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional\d7;

use Drupal\node\Entity\Node;
use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase;
use Drupal\user\Entity\User;

/**
 * Tests Drupal 7 upgrade using the migrate UI.
 *
 * The test method is provided by the MigrateUpgradeTestBase class.
 *
 * @group migrate_drupal_ui
 */
class Upgrade7Test extends MigrateUpgradeExecuteTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'file',
    'language',
    'config_translation',
    'content_translation',
    'migrate_drupal_ui',
    'telephone',
    'aggregator',
    'book',
    'forum',
    'rdf',
    'statistics',
    'migration_provider_test',
    // Required for translation migrations.
    'migrate_drupal_multilingual',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadFixture(drupal_get_path('module', 'migrate_drupal') . '/tests/fixtures/drupal7.php');
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath() {
    return __DIR__ . '/files';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts() {
    return [
      'aggregator_item' => 11,
      'aggregator_feed' => 1,
      'block' => 25,
      'block_content' => 1,
      'block_content_type' => 1,
      'comment' => 3,
      // The 'standard' profile provides the 'comment' comment type, and the
      // migration creates 6 comment types, one per node type.
      'comment_type' => 7,
      // Module 'language' comes with 'en', 'und', 'zxx'. Migration adds 'is'
      // and 'fr'.
      'configurable_language' => 5,
      'contact_form' => 3,
      'editor' => 2,
      'field_config' => 67,
      'field_storage_config' => 50,
      'file' => 3,
      'filter_format' => 7,
      'image_style' => 6,
      'language_content_settings' => 6,
      'migration' => 73,
      'node' => 5,
      'node_type' => 6,
      'rdf_mapping' => 8,
      'search_page' => 2,
      'shortcut' => 6,
      'shortcut_set' => 2,
      'action' => 17,
      'menu' => 6,
      'taxonomy_term' => 18,
      'taxonomy_vocabulary' => 4,
      'tour' => 5,
      'user' => 4,
      'user_role' => 3,
      'menu_link_content' => 12,
      'view' => 16,
      'date_format' => 11,
      'entity_form_display' => 17,
      'entity_form_mode' => 1,
      'entity_view_display' => 28,
      'entity_view_mode' => 14,
      'base_field_override' => 9,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
    $counts = $this->getEntityCounts();
    $counts['block_content'] = 2;
    $counts['comment'] = 4;
    $counts['file'] = 4;
    $counts['menu_link_content'] = 13;
    $counts['node'] = 6;
    $counts['taxonomy_term'] = 19;
    $counts['user'] = 5;
    return $counts;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths() {
    return [
      'aggregator',
      'block',
      'book',
      'color',
      'comment',
      'contact',
      'date',
      'dblog',
      'email',
      'entityreference',
      'field',
      'field_sql_storage',
      'file',
      'filter',
      'forum',
      'i18n_variable',
      'image',
      'language',
      'link',
      'list',
      'locale',
      'menu',
      'node',
      'number',
      'options',
      'path',
      'phone',
      'rdf',
      'search',
      'shortcut',
      'statistics',
      'system',
      'taxonomy',
      'text',
      'user',
      // Include modules that do not have an upgrade path and are enabled in the
      // source database, defined in the $noUpgradePath property
      // in MigrateUpgradeForm.
      'blog',
      'contextual',
      'date_api',
      'entity',
      'field_ui',
      'help',
      'php',
      'simpletest',
      'toolbar',
      'translation',
      'trigger',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [
      'i18n',
      'variable',
      'variable_realm',
      'variable_store',
      // These modules are in the missing path list because they are installed
      // on the source site but they are not installed on the destination site.
      'syslog',
      'tracker',
      'update',
    ];
  }

  /**
   * Executes all steps of migrations upgrade.
   */
  public function testMigrateUpgradeExecute() {
    parent::testMigrateUpgradeExecute();

    // Ensure migrated users can log in.
    $user = User::load(2);
    $user->passRaw = 'a password';
    $this->drupalLogin($user);
    $this->assertFollowUpMigrationResults();
  }

  /**
   * Tests that follow-up migrations have been run successfully.
   */
  protected function assertFollowUpMigrationResults() {
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
