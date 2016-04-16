<?php

namespace Drupal\Tests\node\Kernel\Migrate\d7;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;

/**
 * Upgrade node types to node.type.*.yml.
 *
 * @group node
 */
class MigrateNodeTypeTest extends MigrateDrupal7TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'text', 'filter');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('node'));
    $this->executeMigration('d7_node_type');
  }

  /**
   * Tests a single node type.
   *
   * @dataProvider testNodeTypeDataProvider
   * @param string $id
   *   The node type ID.
   * @param string $label
   *   The expected label.
   * @param string $description
   *   The expected node type description.
   * @param string $help
   *   The expected help text.
   */
  protected function assertEntity($id, $label, $description, $help, $display_submitted, $new_revision, $body_label = NULL) {
    /** @var \Drupal\node\NodeTypeInterface $entity */
    $entity = NodeType::load($id);
    $this->assertTrue($entity instanceof NodeTypeInterface);
    $this->assertIdentical($label, $entity->label());
    $this->assertIdentical($description, $entity->getDescription());
    $this->assertIdentical($help, $entity->getHelp());

    $this->assertIdentical($display_submitted, $entity->displaySubmitted(), 'Submission info is displayed');
    $this->assertIdentical($new_revision, $entity->isNewRevision(), 'Is a new revision');

    if ($body_label) {
      /** @var \Drupal\field\FieldConfigInterface $body */
      $body = FieldConfig::load('node.' . $id . '.body');
      $this->assertTrue($body instanceof FieldConfigInterface);
      $this->assertIdentical($body_label, $body->label());
    }
  }

  /**
   * Tests Drupal 7 node type to Drupal 8 migration.
   */
  public function testNodeType() {
    $this->assertEntity('article', 'Article', 'Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.', 'Help text for articles', TRUE, FALSE, "Body");
    $this->assertEntity('blog', 'Blog entry', 'Use for multi-user blogs. Every user gets a personal blog.', 'Blog away, good sir!', TRUE, FALSE, 'Body');
    // book's display_submitted flag is not set, so it will default to TRUE.
    $this->assertEntity('book', 'Book page', '<em>Books</em> have a built-in hierarchical navigation. Use for handbooks or tutorials.', '', TRUE, TRUE, "Body");
    $this->assertEntity('forum', 'Forum topic', 'A <em>forum topic</em> starts a new discussion thread within a forum.', 'No name-calling, no flame wars. Be nice.', TRUE, FALSE, 'Body');
    $this->assertEntity('page', 'Basic page', "Use <em>basic pages</em> for your static content, such as an 'About us' page.", 'Help text for basic pages', FALSE, FALSE, "Body");
    // This node type does not carry a body field.
    $this->assertEntity('test_content_type', 'Test content type', 'This is the description of the test content type.', 'Help text for test content type', FALSE, TRUE);
  }

}
