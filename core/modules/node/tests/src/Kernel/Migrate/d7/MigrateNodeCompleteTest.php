<?php

namespace Drupal\Tests\node\Kernel\Migrate\d7;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\NodeMigrateType;
use Drupal\node\NodeInterface;
use Drupal\Tests\file\Kernel\Migrate\d7\FileMigrationSetupTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\Tests\migrate_drupal\Traits\CreateTestContentEntitiesTrait;
use Drupal\Tests\migrate_drupal\Traits\NodeMigrateTypeTestTrait;

/**
 * Test class for a complete node migration for Drupal 7.
 *
 * @group migrate_drupal_7
 */
class MigrateNodeCompleteTest extends MigrateDrupal7TestBase {

  use FileMigrationSetupTrait;
  use CreateTestContentEntitiesTrait;
  use NodeMigrateTypeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'comment',
    'datetime',
    'image',
    'language',
    'link',
    'menu_ui',
    // Required for translation migrations.
    'migrate_drupal_multilingual',
    'node',
    'taxonomy',
    'telephone',
    'text',
  ];

  /**
   * The entity storage for node.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Remove the classic node table made in setup.
    $this->removeNodeMigrateMapTable(NodeMigrateType::NODE_MIGRATE_TYPE_CLASSIC, '7');

    $this->fileMigrationSetup();

    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('system', ['sequences']);

    $this->createContent();

    $this->nodeStorage = $this->container->get('entity_type.manager')
      ->getStorage('node');
    $this->nodeStorage->delete($this->nodeStorage->loadMultiple());

    $this->migrateUsers();
    $this->migrateFields();
    $this->executeMigrations([
      'language',
      'd7_language_content_settings',
      'd7_comment_field',
      'd7_comment_field_instance',
      'd7_node_complete',
    ]);
    $this->nodeStorage = $this->container->get('entity_type.manager')
      ->getStorage('node');
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
   * Tests the complete node migration.
   */
  public function testNodeCompleteMigration() {
    // Confirm there are only complete node migration map tables. This shows
    // that only the complete migration ran.
    $results = $this->nodeMigrateMapTableCount('7');
    $this->assertSame(0, $results['node']);
    $this->assertSame(8, $results['node_complete']);

    $db = \Drupal::database();
    $this->assertEquals($this->expectedNodeFieldRevisionTable(), $db->select('node_field_revision', 'nr')
      ->fields('nr')
      ->orderBy('vid')
      ->orderBy('langcode')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC));
    $this->assertEquals($this->expectedNodeFieldDataTable(), $db->select('node_field_data', 'nr')
      ->fields('nr')
      ->orderBy('nid')
      ->orderBy('vid')
      ->orderBy('langcode')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC));

    // Load and test each revision.
    $data = $this->expectedRevisionEntityData()[0];
    foreach ($this->expectedNodeFieldRevisionTable() as $key => $revision) {
      $this->assertRevision($revision, $data[$key]);
    }
  }

  /**
   * Tests rollback of the complete node migration.
   */
  public function testRollbackNodeComplete() {
    $db = \Drupal::database();
    $node_types = [
      'article',
      'blog',
      'book',
      'forum',
      'page',
      'test_content_type',
    ];
    foreach ($node_types as $node_type) {
      // Execute the rollback.
      $this->migration = $this->getMigration("d7_node_complete:$node_type");
      (new MigrateExecutable($this->migration, $this))->rollback();

      // Assert there are no nodes of node_type.
      $count = $db->select('node_field_data')
        ->condition('type', $node_type)
        ->countQuery()
        ->execute()
        ->fetchField();
      $this->assertSame($count, '0', "There are $count nodes of type $node_type");
    }
  }

  /**
   * Asserts various aspects of a node revision.
   *
   * @param array $revision
   *   An array of revision data matching a node_field_revision table row.
   * @param array $data
   *   An array of revision data.
   */
  protected function assertRevision(array $revision, array $data) {
    /** @var  \Drupal\node\NodeInterface $actual */
    $actual = $this->nodeStorage->loadRevision($revision['vid'])
      ->getTranslation($revision['langcode']);
    $this->assertInstanceOf(NodeInterface::class, $actual);
    $this->assertSame($revision['title'], $actual->getTitle(), sprintf("Title '%s' does not match actual '%s' for revision '%d' langcode '%s'", $revision['title'], $actual->getTitle(), $revision['vid'], $revision['langcode']));
    $this->assertSame($revision['revision_translation_affected'], $actual->get('revision_translation_affected')->value, sprintf("revision_translation_affected '%s' does not match actual '%s' for revision '%d' langcode '%s'", $revision['revision_translation_affected'], $actual->get('revision_translation_affected')->value, $revision['vid'], $revision['langcode']));

    $this->assertSame($data['revision_created'], $actual->getRevisionCreationTime(), sprintf("Creation time '%s' does not match actual '%s' for revision '%d' langcode '%s'", $data['revision_created'], $actual->getRevisionCreationTime(), $revision['vid'], $revision['langcode']));
    $this->assertSame($data['log'], $actual->getRevisionLogMessage(), sprintf("Revision log '%s' does not match actual '%s' for revision '%d' langcode '%s'", var_export($data['log'], TRUE), $actual->getRevisionLogMessage(), $revision['vid'], $revision['langcode']));
    if (isset($data['field_text_long_plain'])) {
      $this->assertSame($data['field_text_long_plain'], $actual->field_text_long_plain->value, sprintf("field_text_long_plain value '%s' does not match actual '%s' for revision '%d' langcode '%s'", var_export($data['field_text_long_plain'], TRUE), $actual->field_text_long_plain->value, $revision['vid'], $revision['langcode']));
    }
    if (isset($data['field_tree'])) {
      $this->assertSame($data['field_tree'], $actual->field_tree->value, sprintf("field_tree value '%s' does not match actual '%s' for revision '%d' langcode '%s'", var_export($data['field_tree'], TRUE), $actual->field_tree->value, $revision['vid'], $revision['langcode']));
    }

  }

  /**
   * Provides the expected node_field_data table.
   *
   * @return array
   *   The expected table rows.
   */
  protected function expectedNodeFieldDataTable() {
    return [
      0 =>
        [
          'nid' => '1',
          'vid' => '1',
          'type' => 'test_content_type',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '2',
          'title' => 'An English Node',
          'created' => '1529615790',
          'changed' => '1529615790',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      1 =>
        [
          'nid' => '2',
          'vid' => '12',
          'type' => 'article',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '2',
          'title' => 'The thing about Deep Space 9',
          'created' => '1441306772',
          'changed' => '1564543637',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      2 =>
        [
          'nid' => '2',
          'vid' => '12',
          'type' => 'article',
          'langcode' => 'is',
          'status' => '1',
          'uid' => '1',
          'title' => 'is - The thing about Deep Space 9',
          'created' => '1471428152',
          'changed' => '1564543706',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      3 =>
        [
          'nid' => '4',
          'vid' => '14',
          'type' => 'article',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'en - The thing about Firefly',
          'created' => '1478755314',
          'changed' => '1564543929',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'is',
          'content_translation_outdated' => '0',
        ],
      4 =>
        [
          'nid' => '4',
          'vid' => '14',
          'type' => 'article',
          'langcode' => 'is',
          'status' => '1',
          'uid' => '1',
          'title' => 'is - The thing about Firefly',
          'created' => '1478755274',
          'changed' => '1564543810',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'is',
          'content_translation_outdated' => '0',
        ],
      5 =>
        [
          'nid' => '6',
          'vid' => '6',
          'type' => 'forum',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'Comments are closed :-(',
          'created' => '1504715414',
          'changed' => '1504715414',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      6 =>
        [
          'nid' => '7',
          'vid' => '7',
          'type' => 'forum',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'Comments are open :-)',
          'created' => '1504715432',
          'changed' => '1504715432',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      7 =>
        [
          'nid' => '8',
          'vid' => '10',
          'type' => 'blog',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'The number 47',
          'created' => '1551000341',
          'changed' => '1552126247',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      8 =>
        [
          'nid' => '8',
          'vid' => '10',
          'type' => 'blog',
          'langcode' => 'fr',
          'status' => '1',
          'uid' => '1',
          'title' => 'fr - The number 47',
          'created' => '1552126296',
          'changed' => '1552126296',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      9 =>
        [
          'nid' => '8',
          'vid' => '10',
          'type' => 'blog',
          'langcode' => 'is',
          'status' => '1',
          'uid' => '1',
          'title' => 'is - The number 47',
          'created' => '1552126363',
          'changed' => '1552126363',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      10 =>
        [
          'nid' => '11',
          'vid' => '18',
          'type' => 'et',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'Page one',
          'created' => '1568261523',
          'changed' => '1568261687',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => '',
          'content_translation_outdated' => '0',
        ],
      11 =>
        [
          'nid' => '11',
          'vid' => '18',
          'type' => 'et',
          'langcode' => 'fr',
          'status' => '1',
          'uid' => '1',
          'title' => 'Page one',
          'created' => '1568261721',
          'changed' => '1568261721',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      12 =>
        [
          'nid' => '11',
          'vid' => '18',
          'type' => 'et',
          'langcode' => 'is',
          'status' => '1',
          'uid' => '1',
          'title' => 'Page one',
          'created' => '1568261548',
          'changed' => '1568261548',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
    ];
  }

  /**
   * Provides the expected node_field_revision table.
   *
   * @return array
   *   The table.
   */
  protected function expectedNodeFieldRevisionTable() {
    return [
      0 =>
        [
          'nid' => '1',
          'vid' => '1',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '2',
          'title' => 'An English Node',
          'created' => '1529615790',
          'changed' => '1529615790',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      1 =>
        [
          'nid' => '2',
          'vid' => '2',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '2',
          'title' => 'The thing about Deep Space 9 (1st rev)',
          'created' => '1441306772',
          'changed' => '1564543588',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      2 =>
        [
          'nid' => '2',
          'vid' => '3',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '2',
          'title' => 'The thing about Deep Space 9 (1st rev)',
          'created' => '1441306772',
          'changed' => '1564543588',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      3 =>
        [
          'nid' => '2',
          'vid' => '3',
          'langcode' => 'is',
          'status' => '1',
          'uid' => '1',
          'title' => 'is - The thing about Deep Space 9 (1st rev)',
          'created' => '1471428152',
          'changed' => '1564543677',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      4 =>
        [
          'nid' => '4',
          'vid' => '4',
          'langcode' => 'is',
          'status' => '1',
          'uid' => '1',
          'title' => 'is - The thing about Firefly (1st rev)',
          'created' => '1478755274',
          'changed' => '1478755274',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'is',
          'content_translation_outdated' => '0',
        ],
      5 =>
        [
          'nid' => '4',
          'vid' => '5',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'en - The thing about Firefly (1st rev)',
          'created' => '1478755314',
          'changed' => '1564543887',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'is',
          'content_translation_outdated' => '0',
        ],
      6 =>
        [
          'nid' => '4',
          'vid' => '5',
          'langcode' => 'is',
          'status' => '1',
          'uid' => '1',
          'title' => 'is - The thing about Firefly (1st rev)',
          'created' => '1478755274',
          'changed' => '1478755274',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'is',
          'content_translation_outdated' => '0',
        ],
      7 =>
        [
          'nid' => '6',
          'vid' => '6',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'Comments are closed :-(',
          'created' => '1504715414',
          'changed' => '1504715414',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      8 =>
        [
          'nid' => '7',
          'vid' => '7',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'Comments are open :-)',
          'created' => '1504715432',
          'changed' => '1504715432',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      9 =>
        [
          'nid' => '8',
          'vid' => '8',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'The number 47',
          'created' => '1551000341',
          'changed' => '1552126247',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      10 =>
        [
          'nid' => '8',
          'vid' => '9',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'The number 47',
          'created' => '1551000341',
          'changed' => '1552126247',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      11 =>
        [
          'nid' => '8',
          'vid' => '9',
          'langcode' => 'fr',
          'status' => '1',
          'uid' => '1',
          'title' => 'fr - The number 47',
          'created' => '1552126296',
          'changed' => '1552126296',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      12 =>
        [
          'nid' => '8',
          'vid' => '10',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'The number 47',
          'created' => '1551000341',
          'changed' => '1552126247',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      13 =>
        [
          'nid' => '8',
          'vid' => '10',
          'langcode' => 'fr',
          'status' => '1',
          'uid' => '1',
          'title' => 'fr - The number 47',
          'created' => '1552126296',
          'changed' => '1552126296',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      14 =>
        [
          'nid' => '8',
          'vid' => '10',
          'langcode' => 'is',
          'status' => '1',
          'uid' => '1',
          'title' => 'is - The number 47',
          'created' => '1552126363',
          'changed' => '1552126363',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      15 =>
        [
          'nid' => '2',
          'vid' => '11',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '2',
          'title' => 'The thing about Deep Space 9',
          'created' => '1441306772',
          'changed' => '1564543637',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      16 =>
        [
          'nid' => '2',
          'vid' => '11',
          'langcode' => 'is',
          'status' => '1',
          'uid' => '1',
          'title' => 'is - The thing about Deep Space 9 (1st rev)',
          'created' => '1471428152',
          'changed' => '1564543637',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      17 =>
        [
          'nid' => '2',
          'vid' => '12',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '2',
          'title' => 'The thing about Deep Space 9',
          'created' => '1441306772',
          'changed' => '1564543637',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      18 =>
        [
          'nid' => '2',
          'vid' => '12',
          'langcode' => 'is',
          'status' => '1',
          'uid' => '1',
          'title' => 'is - The thing about Deep Space 9',
          'created' => '1471428152',
          'changed' => '1564543706',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      19 =>
        [
          'nid' => '4',
          'vid' => '13',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'en - The thing about Firefly (1st rev)',
          'created' => '1478755314',
          'changed' => '1564543887',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'is',
          'content_translation_outdated' => '0',
        ],
      20 =>
        [
          'nid' => '4',
          'vid' => '13',
          'langcode' => 'is',
          'status' => '1',
          'uid' => '1',
          'title' => 'is - The thing about Firefly',
          'created' => '1478755274',
          'changed' => '1564543810',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'is',
          'content_translation_outdated' => '0',
        ],
      21 =>
        [
          'nid' => '4',
          'vid' => '14',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'en - The thing about Firefly',
          'created' => '1478755314',
          'changed' => '1564543929',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'is',
          'content_translation_outdated' => '0',
        ],
      22 =>
        [
          'nid' => '4',
          'vid' => '14',
          'langcode' => 'is',
          'status' => '1',
          'uid' => '1',
          'title' => 'is - The thing about Firefly',
          'created' => '1478755274',
          'changed' => '1564543810',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'is',
          'content_translation_outdated' => '0',
        ],
      23 =>
        [
          'nid' => '11',
          'vid' => '15',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'Page one',
          'created' => '1568261523',
          'changed' => '1568261523',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => '1',
          'content_translation_source' => NULL,
          'content_translation_outdated' => '0',
        ],
      24 =>
        [
          'nid' => '11',
          'vid' => '16',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'Page one',
          'created' => '1568261523',
          'changed' => '1568261523',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => '',
          'content_translation_outdated' => '0',
        ],
      25 =>
        [
          'nid' => '11',
          'vid' => '16',
          'langcode' => 'is',
          'status' => '1',
          'uid' => '1',
          'title' => 'Page one',
          'created' => '1568261548',
          'changed' => '1568261548',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      26 =>
        [
          'nid' => '11',
          'vid' => '17',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'Page one',
          'created' => '1568261523',
          'changed' => '1568261687',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => '',
          'content_translation_outdated' => '0',
        ],
      27 =>
        [
          'nid' => '11',
          'vid' => '17',
          'langcode' => 'is',
          'status' => '1',
          'uid' => '1',
          'title' => 'Page one',
          'created' => '1568261548',
          'changed' => '1568261548',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      28 =>
        [
          'nid' => '11',
          'vid' => '18',
          'langcode' => 'en',
          'status' => '1',
          'uid' => '1',
          'title' => 'Page one',
          'created' => '1568261523',
          'changed' => '1568261687',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '1',
          'revision_translation_affected' => NULL,
          'content_translation_source' => '',
          'content_translation_outdated' => '0',
        ],
      29 =>
        [
          'nid' => '11',
          'vid' => '18',
          'langcode' => 'fr',
          'status' => '1',
          'uid' => '1',
          'title' => 'Page one',
          'created' => '1568261721',
          'changed' => '1568261721',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => '1',
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
      30 =>
        [
          'nid' => '11',
          'vid' => '18',
          'langcode' => 'is',
          'status' => '1',
          'uid' => '1',
          'title' => 'Page one',
          'created' => '1568261548',
          'changed' => '1568261548',
          'promote' => '0',
          'sticky' => '0',
          'default_langcode' => '0',
          'revision_translation_affected' => NULL,
          'content_translation_source' => 'en',
          'content_translation_outdated' => '0',
        ],
    ];
  }

  /**
   * Provides the expected node_field_revision table.
   *
   * @return array
   *   Selected properties and fields on the revision.
   */
  protected function expectedRevisionEntityData() {
    return [
      $revision_data = [
        // Node 1, revision 1, en.
        0 =>
          [
            'log' => NULL,
            'field_text_long_plain' => NULL,
            'revision_created' => '1529615790',
          ],
        // Node 2, revision 2, en.
        1 =>
          [
            'log' => 'DS9 1st rev',
            'field_text_long_plain' => 'DS9 1st rev',
            'revision_created' => '1564543588',
          ],
        // Node 2, revision 3, en.
        2 =>
          [
            'log' => 'is - DS9 1st rev',
            'field_text_long_plain' => 'DS9 1st rev',
            'revision_created' => '1564543677',
          ],
        // Node 2, revision 3, is.
        3 =>
          [
            'log' => 'is - DS9 1st rev',
            'field_text_long_plain' => 'is - DS9 1st rev',
            'revision_created' => '1564543677',
          ],
        // Node 4, revision 4, is.
        4 =>
          [
            'log' => 'is - Firefly 1st rev',
            'field_text_long_plain' => NULL,
            'revision_created' => '1478755274',
          ],
        // Node 4, revision 5, en.
        5 =>
          [
            'log' => 'Firefly 1st rev',
            'field_text_long_plain' => NULL,
            'revision_created' => '1564543887',
          ],
        // Node 4, revision 5, is.
        6 =>
          [
            'log' => 'Firefly 1st rev',
            'field_text_long_plain' => NULL,
            'revision_created' => '1564543887',
          ],
        // Node 6, revision 6, en.
        7 =>
          [
            'log' => NULL,
            'field_text_long_plain' => NULL,
            'revision_created' => '1504715414',
          ],
        // Node 7, revision 7, en.
        8 =>
          [
            'log' => NULL,
            'field_text_long_plain' => NULL,
            'revision_created' => '1504715432',
          ],
        // Node 8, revision 8, en.
        9 =>
          [
            'log' => NULL,
            'field_text_long_plain' => NULL,
            'revision_created' => '1552126247',
          ],
        // Node 8, revision 9, en.
        10 =>
          [
            'log' => NULL,
            'field_text_long_plain' => NULL,
            'revision_created' => '1552126296',
          ],
        // Node 8, revision 9, fr.
        11 =>
          [
            'log' => NULL,
            'field_text_long_plain' => NULL,
            'revision_created' => '1552126296',
          ],
        // Node 8, revision 10, en.
        12 =>
          [
            'log' => NULL,
            'field_text_long_plain' => NULL,
            'revision_created' => '1552126363',
          ],
        // Node 8, revision 10, fr.
        13 =>
          [
            'log' => NULL,
            'field_text_long_plain' => NULL,
            'revision_created' => '1552126363',
          ],
        // Node 8, revision 10, is.
        14 =>
          [
            'log' => NULL,
            'field_text_long_plain' => NULL,
            'revision_created' => '1552126363',
          ],
        // Node 2, revision 11, en.
        15 =>
          [
            'log' => 'DS9 2nd rev',
            'field_text_long_plain' => NULL,
            'revision_created' => '1564543637',
          ],
        // Node 2, revision 11, is.
        16 =>
          [
            'log' => 'DS9 2nd rev',
            'field_text_long_plain' => NULL,
            'revision_created' => '1564543637',
          ],
        // Node 2, revision 12, en.
        17 =>
          [
            'log' => 'is - DS9 2nd rev',
            'field_text_long_plain' => NULL,
            'revision_created' => '1564543706',
          ],
        // Node 2, revision 12, is.
        18 =>
          [
            'log' => 'is - DS9 2nd rev',
            'field_text_long_plain' => NULL,
            'revision_created' => '1564543706',
          ],
        // Node 4, revision 13, en.
        19 =>
          [
            'log' => 'is - Firefly 2nd rev',
            'field_text_long_plain' => NULL,
            'revision_created' => '1564543810',
          ],
        // Node 4, revision 13, is.
        20 =>
          [
            'log' => 'is - Firefly 2nd rev',
            'field_text_long_plain' => NULL,
            'revision_created' => '1564543810',
          ],
        // Node 4, revision 14, en.
        21 =>
          [
            'log' => 'Firefly 2nd rev',
            'field_text_long_plain' => NULL,
            'revision_created' => '1564543929',
          ],
        // Node 4, revision 14, is.
        22 =>
          [
            'log' => 'Firefly 2nd rev',
            'field_text_long_plain' => NULL,
            'revision_created' => '1564543929',
          ],
        // Node 11, revision 15, en.
        23 =>
          [
            'log' => NULL,
            'body' => '1st',
            'field_tree' => 'lancewood',
            'revision_created' => '1568261523',
          ],
        // Node 11, revision 16, en.
        24 =>
          [
            'log' => NULL,
            'body' => '1st',
            'field_tree' => 'lancewood',
            'revision_created' => '1568261548',
          ],
        // Node 11, revision 16, is.
        25 =>
          [
            'log' => NULL,
            'body' => '1st',
            'field_tree' => 'is - lancewood',
            'revision_created' => '1568261548',
          ],
        // Node 11, revision 17, en.
        26 =>
          [
            'log' => '2nd',
            'body' => '2nd',
            'field_tree' => 'lancewood',
            'revision_created' => '1568261548',
          ],
        // Node 11, revision 17, is.
        27 =>
          [
            'log' => '2nd',
            'body' => '2nd',
            'field_tree' => 'is - lancewood',
            'revision_created' => '1568261548',
          ],
        // Node 11, revision 18, en.
        28 =>
          [
            'log' => NULL,
            'body' => '2nd',
            'field_tree' => 'lancewood',
            'revision_created' => '1568261548',
          ],
        // Node 11, revision 18, f5.
        29 =>
          [
            'log' => NULL,
            'body' => '2nd',
            'field_tree' => 'fr - lancewood',
            'revision_created' => '1568261548',
          ],
        // Node 11, revision 18, is.
        30 =>
          [
            'log' => NULL,
            'body' => '2nd',
            'field_tree' => 'is - lancewood',
            'revision_created' => '1568261548',
          ],
      ],
    ];
  }

}
