<?php

namespace Drupal\Tests\node\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Tests node migration.
 *
 * @group node
 */
class MigrateNodeTest extends MigrateDrupal7TestBase {

  static $modules = array(
    'comment',
    'datetime',
    'filter',
    'image',
    'link',
    'node',
    'taxonomy',
    'telephone',
    'text',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('file');
    $this->installConfig(static::$modules);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('system', ['sequences']);

    $this->executeMigrations([
      'd7_user_role',
      'd7_user',
      'd7_node_type',
      'd7_comment_type',
      'd7_field',
      'd7_field_instance',
      'd7_node:test_content_type',
      'd7_node:article',
    ]);
  }

  /**
   * Asserts various aspects of a node.
   *
   * @param string $id
   *   The node ID.
   * @param string $type
   *   The node type.
   * @param string $langcode
   *   The expected language code.
   * @param string $title
   *   The expected title.
   * @param int $uid
   *   The expected author ID.
   * @param bool $status
   *   The expected status of the node.
   * @param int $created
   *   The expected creation time.
   * @param int $changed
   *   The expected modification time.
   * @param bool $promoted
   *   Whether the node is expected to be promoted to the front page.
   * @param bool $sticky
   *   Whether the node is expected to be sticky.
   */
  protected function assertEntity($id, $type, $langcode, $title, $uid, $status, $created, $changed, $promoted, $sticky) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::load($id);
    $this->assertTrue($node instanceof NodeInterface);
    $this->assertIdentical($type, $node->getType());
    $this->assertIdentical($langcode, $node->langcode->value);
    $this->assertIdentical($title, $node->getTitle());
    $this->assertIdentical($uid, $node->getOwnerId());
    $this->assertIdentical($status, $node->isPublished());
    $this->assertIdentical($created, $node->getCreatedTime());
    if (isset($changed)) {
      $this->assertIdentical($changed, $node->getChangedTime());
    }
    $this->assertIdentical($promoted, $node->isPromoted());
    $this->assertIdentical($sticky, $node->isSticky());
  }

  /**
   * Asserts various aspects of a node revision.
   *
   * @param int $id
   *   The revision ID.
   * @param string $title
   *   The expected title.
   * @param int $uid
   *   The revision author ID.
   * @param string $log
   *   The revision log message.
   * @param int $timestamp
   *   The revision's time stamp.
   */
  protected function assertRevision($id, $title, $uid, $log, $timestamp) {
    $revision = \Drupal::entityManager()->getStorage('node')->loadRevision($id);
    $this->assertTrue($revision instanceof NodeInterface);
    $this->assertIdentical($title, $revision->getTitle());
    $this->assertIdentical($uid, $revision->getRevisionAuthor()->id());
    $this->assertIdentical($log, $revision->revision_log->value);
    $this->assertIdentical($timestamp, $revision->getRevisionCreationTime());
  }

  /**
   * Test node migration from Drupal 7 to 8.
   */
  public function testNode() {
    $this->assertEntity(1, 'test_content_type', 'en', 'A Node', '2', TRUE, '1421727515', '1441032132', TRUE, FALSE);
    $this->assertRevision(1, 'A Node', '1', NULL, '1441032132');

    $node = Node::load(1);
    $this->assertTrue($node->field_boolean->value);
    $this->assertIdentical('99-99-99-99', $node->field_phone->value);
    // Use assertEqual() here instead, since SQLite interprets floats strictly.
    $this->assertEqual('1', $node->field_float->value);
    $this->assertIdentical('5', $node->field_integer->value);
    $this->assertIdentical('Some more text', $node->field_text_list[0]->value);
    $this->assertIdentical('7', $node->field_integer_list[0]->value);
    $this->assertIdentical('qwerty', $node->field_text->value);
    $this->assertIdentical('2', $node->field_file->target_id);
    $this->assertIdentical('file desc', $node->field_file->description);
    $this->assertTrue($node->field_file->display);
    $this->assertIdentical('1', $node->field_images->target_id);
    $this->assertIdentical('alt text', $node->field_images->alt);
    $this->assertIdentical('title text', $node->field_images->title);
    $this->assertIdentical('93', $node->field_images->width);
    $this->assertIdentical('93', $node->field_images->height);

    $node = Node::load(2);
    $this->assertIdentical("...is that it's the absolute best show ever. Trust me, I would know.", $node->body->value);
  }

}
