<?php

namespace Drupal\Tests\node\Kernel\Migrate\d7;

use Drupal\node\NodeInterface;
use Drupal\Tests\file\Kernel\Migrate\d7\FileMigrationSetupTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests node revision migrations.
 *
 * @group node
 */
class MigrateNodeRevisionTest extends MigrateDrupal7TestBase {

  use FileMigrationSetupTrait;

  /**
   * The entity storage for node.
   *
   * @var \Drupal\Core\Entity\RevisionableStorageInterface
   */
  protected $nodeStorage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'comment',
    'datetime',
    'datetime_range',
    'file',
    'filter',
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
  protected function setUp(): void {
    parent::setUp();

    $this->fileMigrationSetup();

    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installSchema('node', ['node_access']);

    $this->migrateUsers();
    $this->migrateFields();
    $this->executeMigrations([
      'language',
      'd7_language_content_settings',
      'd7_comment_field',
      'd7_comment_field_instance',
      'd7_node',
      'd7_node_translation',
      'd7_node_revision',
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
      'size' => 3620,
      'base_path' => 'public://',
      'plugin_id' => 'd7_file',
    ];
  }

  /**
   * Asserts various aspects of a node revision.
   *
   * @param int $id
   *   The revision ID.
   * @param string $langcode
   *   The revision language.
   * @param string $title
   *   The expected title.
   * @param string|null $log
   *   The revision log message.
   * @param int $timestamp
   *   The revision's time stamp.
   *
   * @internal
   */
  protected function assertRevision(int $id, string $langcode, string $title, ?string $log, int $timestamp): void {
    $revision = $this->nodeStorage->loadRevision($id);
    $this->assertInstanceOf(NodeInterface::class, $revision);
    $this->assertSame($title, $revision->getTitle());
    $this->assertSame($langcode, $revision->language()->getId());
    $this->assertSame($log, $revision->revision_log->value);
    $this->assertSame($timestamp, (int) $revision->getRevisionCreationTime());
  }

  /**
   * Tests the migration of node revisions with translated nodes.
   */
  public function testNodeRevisions() {
    $this->assertRevision(1, 'en', 'An English Node', NULL, 1441032132);
    $this->assertRevision(2, 'en', 'The thing about Deep Space 9 (1st rev)', 'DS9 1st rev', 1564543588);
    $this->assertRevision(4, 'is', 'is - The thing about Firefly (1st rev)', 'is - Firefly 1st rev', 1478755274);
    $this->assertRevision(6, 'en', 'Comments are closed :-(', NULL, 1504715414);
    $this->assertRevision(7, 'en', 'Comments are open :-)', NULL, 1504715432);
    $this->assertRevision(8, 'en', 'The number 47', NULL, 1552126363);

    // Test that the revision translation are not migrated and there should not
    // be a revision with id of 9.
    $ids = [3, 5, 9];
    foreach ($ids as $id) {
      $this->assertNull($this->nodeStorage->loadRevision($id));
    }

    // Test the migration of node and user reference fields.
    $revision = $this->nodeStorage->loadRevision(2);
    $this->assertCount(1, $revision->field_node_reference);
    $this->assertSame('5', $revision->field_node_reference->target_id);

    $this->assertCount(1, $revision->field_user_reference);
    $this->assertSame('Bob', $revision->field_user_reference[0]->entity->getAccountName());
  }

}
