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
  public static $modules = [
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
  ];

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
    NodeType::create([
      'type' => $id,
      'label' => $this->randomString(),
    ])->save();

    CommentType::create([
      'id' => 'comment_node_' . $id,
      'label' => $this->randomString(),
      'target_entity_type_id' => 'node',
    ])->save();
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
   * @param bool $expected_translatable
   *   Whether or not the field is expected to be translatable.
   */
  protected function assertEntity($id, $expected_label, $expected_field_type, $is_required, $expected_translatable) {
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
    $this->assertSame($expected_translatable, $field->isTranslatable());
  }

  /**
   * Asserts the settings of a link field config entity.
   *
   * @param $id
   *   The entity ID in the form ENTITY_TYPE.BUNDLE.FIELD_NAME.
   * @param $title_setting
   *   The expected title setting.
   */
  protected function assertLinkFields($id, $title_setting) {
    $field = FieldConfig::load($id);
    $this->assertSame($title_setting, $field->getSetting('title'));
  }

  /**
   * Tests migrating D7 field instances to field_config entities.
   */
  public function testFieldInstances() {
    $this->assertEntity('comment.comment_node_page.comment_body', 'Comment', 'text_long', TRUE, FALSE);
    $this->assertEntity('node.page.body', 'Body', 'text_with_summary', FALSE, FALSE);
    $this->assertEntity('comment.comment_node_article.comment_body', 'Comment', 'text_long', TRUE, FALSE);
    $this->assertEntity('node.article.body', 'Body', 'text_with_summary', FALSE, TRUE);
    $this->assertEntity('node.article.field_tags', 'Tags', 'entity_reference', FALSE, TRUE);
    $this->assertEntity('node.article.field_image', 'Image', 'image', FALSE, TRUE);
    $this->assertEntity('comment.comment_node_blog.comment_body', 'Comment', 'text_long', TRUE, FALSE);
    $this->assertEntity('node.blog.body', 'Body', 'text_with_summary', FALSE, TRUE);
    $this->assertEntity('comment.comment_node_book.comment_body', 'Comment', 'text_long', TRUE, FALSE);
    $this->assertEntity('node.book.body', 'Body', 'text_with_summary', FALSE, FALSE);
    $this->assertEntity('node.forum.taxonomy_forums', 'Forums', 'entity_reference', TRUE, FALSE);
    $this->assertEntity('comment.comment_node_forum.comment_body', 'Comment', 'text_long', TRUE, FALSE);
    $this->assertEntity('node.forum.body', 'Body', 'text_with_summary', FALSE, FALSE);
    $this->assertEntity('comment.comment_node_test_content_type.comment_body', 'Comment', 'text_long', TRUE, FALSE);
    $this->assertEntity('node.test_content_type.field_boolean', 'Boolean', 'boolean', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_email', 'Email', 'email', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_phone', 'Phone', 'telephone', TRUE, FALSE);
    $this->assertEntity('node.test_content_type.field_date', 'Date', 'datetime', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_date_with_end_time', 'Date With End Time', 'datetime', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_file', 'File', 'file', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_float', 'Float', 'float', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_images', 'Images', 'image', TRUE, FALSE);
    $this->assertEntity('node.test_content_type.field_integer', 'Integer', 'integer', TRUE, FALSE);
    $this->assertEntity('node.test_content_type.field_link', 'Link', 'link', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_text_list', 'Text List', 'list_string', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_integer_list', 'Integer List', 'list_integer', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_long_text', 'Long text', 'text_with_summary', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_term_reference', 'Term Reference', 'entity_reference', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_text', 'Text', 'text', FALSE, FALSE);
    $this->assertEntity('comment.comment_node_test_content_type.field_integer', 'Integer', 'integer', FALSE, FALSE);
    $this->assertEntity('user.user.field_file', 'File', 'file', FALSE, FALSE);


    $this->assertLinkFields('node.test_content_type.field_link', DRUPAL_OPTIONAL);
    $this->assertLinkFields('node.article.field_link', DRUPAL_DISABLED);
    $this->assertLinkFields('node.blog.field_link', DRUPAL_REQUIRED);
  }

}
