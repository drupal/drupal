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
  protected static $modules = ['node', 'text', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrateContentTypes();
  }

  /**
   * Tests a single node type.
   *
   * @dataProvider testNodeTypeDataProvider
   *
   * @param string $id
   *   The node type ID.
   * @param string $label
   *   The expected label.
   * @param string $description
   *   The expected node type description.
   * @param string $help
   *   The expected help text.
   * @param bool $display_submitted
   * @param bool $new_revision
   * @param array $expected_available_menus
   * @param string $expected_parent
   * @param string|null $body_label
   */
  protected function assertEntity($id, $label, $description, $help, $display_submitted, $new_revision, $expected_available_menus, $expected_parent, $body_label = NULL) {
    /** @var \Drupal\node\NodeTypeInterface $entity */
    $entity = NodeType::load($id);
    $this->assertInstanceOf(NodeTypeInterface::class, $entity);
    $this->assertSame($label, $entity->label());
    $this->assertSame($description, $entity->getDescription());

    $this->assertSame($help, $entity->getHelp());

    $this->assertSame($display_submitted, $entity->displaySubmitted(), 'Submission info is displayed');
    $this->assertSame($new_revision, $entity->shouldCreateNewRevision(), 'Is a new revision');

    if ($body_label) {
      /** @var \Drupal\field\FieldConfigInterface $body */
      $body = FieldConfig::load('node.' . $id . '.body');
      $this->assertInstanceOf(FieldConfigInterface::class, $body);
      $this->assertSame($body_label, $body->label());
    }

    $this->assertSame($expected_available_menus, $entity->getThirdPartySetting('menu_ui', 'available_menus'));
    $this->assertSame($expected_parent, $entity->getThirdPartySetting('menu_ui', 'parent'));
  }

  /**
   * Tests Drupal 7 node type to Drupal 8 migration.
   */
  public function testNodeType() {
    $expected_available_menus = ['main-menu'];
    $expected_parent = 'main-menu:0:';

    $this->assertEntity('article', 'Article', 'Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.', 'Help text for articles', TRUE, FALSE, $expected_available_menus, $expected_parent, "Body");
    $this->assertEntity('blog', 'Blog entry', 'Use for multi-user blogs. Every user gets a personal blog.', 'Blog away, good sir!', TRUE, FALSE, $expected_available_menus, $expected_parent, 'Body');
    // book's display_submitted flag is not set, so it will default to TRUE.
    $this->assertEntity('book', 'Book page', '<em>Books</em> have a built-in hierarchical navigation. Use for handbooks or tutorials.', '', TRUE, TRUE, $expected_available_menus, $expected_parent, "Body");
    $this->assertEntity('forum', 'Forum topic', 'A <em>forum topic</em> starts a new discussion thread within a forum.', 'No name-calling, no flame wars. Be nice.', TRUE, FALSE, $expected_available_menus, $expected_parent, 'Body');
    $this->assertEntity('page', 'Basic page', "Use <em>basic pages</em> for your static content, such as an 'About us' page.", 'Help text for basic pages', FALSE, FALSE, $expected_available_menus, $expected_parent, "Body");
    // Test the 32 character type name exists.
    $this->assertEntity('a_thirty_two_character_type_name', 'Test long name', '', '', TRUE, FALSE, NULL, NULL, "Body");

    // This node type does not carry a body field.
    $expected_available_menus = [
      'main-menu',
      'management',
      'navigation',
      'user-menu',
    ];
    $this->assertEntity('test_content_type', 'Test content type', 'This is the description of the test content type.', 'Help text for test content type', FALSE, TRUE, $expected_available_menus, $expected_parent);
  }

}
