<?php

namespace Drupal\Tests\field\Kernel\Migrate\d7;

use Drupal\comment\Entity\CommentType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\node\Entity\NodeType;

/**
 * Migrates Drupal 7 field instances.
 *
 * @group field
 */
class MigrateFieldInstanceTest extends MigrateDrupal7TestBase {

  /**
   * The modules to be enabled during the test.
   *
   * @var array
   */
  public static $modules = array(
    'comment',
    'datetime',
    'file',
    'image',
    'link',
    'node',
    'system',
    'taxonomy',
    'telephone',
    'text',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->createType('page');
    $this->createType('article');
    $this->createType('blog');
    $this->createType('book');
    $this->createType('forum');
    $this->createType('test_content_type');
    Vocabulary::create(['vid' => 'test_vocabulary'])->save();
    $this->executeMigrations(['d7_field', 'd7_field_instance']);
  }

  /**
   * Creates a node type with a corresponding comment type.
   *
   * @param string $id
   *   The node type ID.
   */
  protected function createType($id) {
    NodeType::create(array(
      'type' => $id,
      'label' => $this->randomString(),
    ))->save();

    CommentType::create(array(
      'id' => 'comment_node_' . $id,
      'label' => $this->randomString(),
      'target_entity_type_id' => 'node',
    ))->save();
  }

  /**
   * Asserts various aspects of a field config entity.
   *
   * @param string $id
   *   The entity ID in the form ENTITY_TYPE.BUNDLE.FIELD_NAME.
   * @param string $expected_label
   *   The expected field label.
   * @param string $expected_field_type
   *   The expected field type.
   * @param bool $is_required
   *   Whether or not the field is required.
   */
  protected function assertEntity($id, $expected_label, $expected_field_type, $is_required) {
    list ($expected_entity_type, $expected_bundle, $expected_name) = explode('.', $id);

    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = FieldConfig::load($id);
    $this->assertTrue($field instanceof FieldConfigInterface);
    $this->assertIdentical($expected_label, $field->label());
    $this->assertIdentical($expected_field_type, $field->getType());
    $this->assertIdentical($expected_entity_type, $field->getTargetEntityTypeId());
    $this->assertIdentical($expected_bundle, $field->getTargetBundle());
    $this->assertIdentical($expected_name, $field->getName());
    $this->assertEqual($is_required, $field->isRequired());
    $this->assertIdentical($expected_entity_type . '.' . $expected_name, $field->getFieldStorageDefinition()->id());
  }

  /**
   * Tests migrating D7 field instances to field_config entities.
   */
  public function testFieldInstances() {
    $this->assertEntity('comment.comment_node_page.comment_body', 'Comment', 'text_long', TRUE);
    $this->assertEntity('node.page.body', 'Body', 'text_with_summary', FALSE);
    $this->assertEntity('comment.comment_node_article.comment_body', 'Comment', 'text_long', TRUE);
    $this->assertEntity('node.article.body', 'Body', 'text_with_summary', FALSE);
    $this->assertEntity('node.article.field_tags', 'Tags', 'entity_reference', FALSE);
    $this->assertEntity('node.article.field_image', 'Image', 'image', FALSE);
    $this->assertEntity('comment.comment_node_blog.comment_body', 'Comment', 'text_long', TRUE);
    $this->assertEntity('node.blog.body', 'Body', 'text_with_summary', FALSE);
    $this->assertEntity('comment.comment_node_book.comment_body', 'Comment', 'text_long', TRUE);
    $this->assertEntity('node.book.body', 'Body', 'text_with_summary', FALSE);
    $this->assertEntity('node.forum.taxonomy_forums', 'Forums', 'entity_reference', TRUE);
    $this->assertEntity('comment.comment_node_forum.comment_body', 'Comment', 'text_long', TRUE);
    $this->assertEntity('node.forum.body', 'Body', 'text_with_summary', FALSE);
    $this->assertEntity('comment.comment_node_test_content_type.comment_body', 'Comment', 'text_long', TRUE);
    $this->assertEntity('node.test_content_type.field_boolean', 'Boolean', 'boolean', FALSE);
    $this->assertEntity('node.test_content_type.field_email', 'Email', 'email', FALSE);
    $this->assertEntity('node.test_content_type.field_phone', 'Phone', 'telephone', TRUE);
    $this->assertEntity('node.test_content_type.field_date', 'Date', 'datetime', FALSE);
    $this->assertEntity('node.test_content_type.field_date_with_end_time', 'Date With End Time', 'datetime', FALSE);
    $this->assertEntity('node.test_content_type.field_file', 'File', 'file', FALSE);
    $this->assertEntity('node.test_content_type.field_float', 'Float', 'float', FALSE);
    $this->assertEntity('node.test_content_type.field_images', 'Images', 'image', TRUE);
    $this->assertEntity('node.test_content_type.field_integer', 'Integer', 'integer', TRUE);
    $this->assertEntity('node.test_content_type.field_link', 'Link', 'link', FALSE);
    $this->assertEntity('node.test_content_type.field_text_list', 'Text List', 'list_string', FALSE);
    $this->assertEntity('node.test_content_type.field_integer_list', 'Integer List', 'list_integer', FALSE);
    $this->assertEntity('node.test_content_type.field_long_text', 'Long text', 'text_with_summary', FALSE);
    $this->assertEntity('node.test_content_type.field_term_reference', 'Term Reference', 'entity_reference', FALSE);
    $this->assertEntity('node.test_content_type.field_text', 'Text', 'text', FALSE);
    $this->assertEntity('comment.comment_node_test_content_type.field_integer', 'Integer', 'integer', FALSE);
    $this->assertEntity('user.user.field_file', 'File', 'file', FALSE);
  }

}
