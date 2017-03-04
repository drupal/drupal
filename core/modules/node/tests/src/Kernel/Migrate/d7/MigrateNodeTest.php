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

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_translation',
    'comment',
    'datetime',
    'filter',
    'image',
    'language',
    'link',
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

    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('file');
    $this->installConfig(static::$modules);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('system', ['sequences']);

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
    $this->assertIdentical($uid, $revision->getRevisionUser()->id());
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
    $this->assertIdentical('http://google.com', $node->field_link->uri);
    $this->assertIdentical('Click Here', $node->field_link->title);

    $node = Node::load(2);
    $this->assertSame('en', $node->langcode->value);
    $this->assertIdentical("...is that it's the absolute best show ever. Trust me, I would know.", $node->body->value);
    $this->assertSame('The thing about Deep Space 9', $node->label());
    $this->assertIdentical('internal:/', $node->field_link->uri);
    $this->assertIdentical('Home', $node->field_link->title);
    $this->assertTrue($node->hasTranslation('is'), "Node 2 has an Icelandic translation");

    $translation = $node->getTranslation('is');
    $this->assertSame('is', $translation->langcode->value);
    $this->assertSame("is - ...is that it's the absolute best show ever. Trust me, I would know.", $translation->body->value);
    $this->assertSame('is - The thing about Deep Space 9', $translation->label());
    $this->assertSame('internal:/', $translation->field_link->uri);
    $this->assertSame('Home', $translation->field_link->title);

    // Test that content_translation_source is set.
    $manager = $this->container->get('content_translation.manager');
    $this->assertSame('en', $manager->getTranslationMetadata($node->getTranslation('is'))->getSource());

    // Node 3 is a translation of node 2, and should not be imported separately.
    $this->assertNull(Node::load(3), "Node 3 doesn't exist in D8, it was a translation");

    // Test that content_translation_source for a source other than English.
    $node = Node::load(4);
    $this->assertSame('is', $manager->getTranslationMetadata($node->getTranslation('en'))->getSource());

  }

}
