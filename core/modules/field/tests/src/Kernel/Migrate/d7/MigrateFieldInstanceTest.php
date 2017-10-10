<?php

namespace Drupal\Tests\field\Kernel\Migrate\d7;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\migrate\Kernel\NodeCommentCombinationTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Migrates Drupal 7 field instances.
 *
 * @group field
 */
class MigrateFieldInstanceTest extends MigrateDrupal7TestBase {

  use NodeCommentCombinationTrait;

  /**
   * {@inheritdoc}
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
    $this->createNodeCommentCombination('page');
    $this->createNodeCommentCombination('article');
    $this->createNodeCommentCombination('blog');
    $this->createNodeCommentCombination('book');
    $this->createNodeCommentCombination('forum', 'comment_forum');
    $this->createNodeCommentCombination('test_content_type');
    Vocabulary::create(['vid' => 'test_vocabulary'])->save();
    $this->executeMigrations(['d7_field', 'd7_field_instance']);
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
    $this->assertInstanceOf(FieldConfigInterface::class, $field);
    $this->assertEquals($expected_label, $field->label());
    $this->assertEquals($expected_field_type, $field->getType());
    $this->assertEquals($expected_entity_type, $field->getTargetEntityTypeId());
    $this->assertEquals($expected_bundle, $field->getTargetBundle());
    $this->assertEquals($expected_name, $field->getName());
    $this->assertEquals($is_required, $field->isRequired());
    $this->assertEquals($expected_entity_type . '.' . $expected_name, $field->getFieldStorageDefinition()->id());
    $this->assertEquals($expected_translatable, $field->isTranslatable());
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
    $this->assertEntity('comment.comment_forum.comment_body', 'Comment', 'text_long', TRUE, FALSE);
    $this->assertEntity('node.forum.body', 'Body', 'text_with_summary', FALSE, FALSE);
    $this->assertEntity('comment.comment_node_test_content_type.comment_body', 'Comment', 'text_long', TRUE, FALSE);
    $this->assertEntity('node.test_content_type.field_boolean', 'Boolean', 'boolean', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_email', 'Email', 'email', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_phone', 'Phone', 'telephone', TRUE, FALSE);
    $this->assertEntity('node.test_content_type.field_date', 'Date', 'datetime', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_date_with_end_time', 'Date With End Time', 'timestamp', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_file', 'File', 'file', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_float', 'Float', 'float', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_images', 'Images', 'image', TRUE, FALSE);
    $this->assertEntity('node.test_content_type.field_integer', 'Integer', 'integer', TRUE, FALSE);
    $this->assertEntity('node.test_content_type.field_link', 'Link', 'link', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_text_list', 'Text List', 'list_string', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_integer_list', 'Integer List', 'list_integer', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_long_text', 'Long text', 'text_with_summary', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_term_reference', 'Term Reference', 'entity_reference', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_text', 'Text', 'string', FALSE, FALSE);
    $this->assertEntity('comment.comment_node_test_content_type.field_integer', 'Integer', 'integer', FALSE, FALSE);
    $this->assertEntity('user.user.field_file', 'File', 'file', FALSE, FALSE);


    $this->assertLinkFields('node.test_content_type.field_link', DRUPAL_OPTIONAL);
    $this->assertLinkFields('node.article.field_link', DRUPAL_DISABLED);
    $this->assertLinkFields('node.blog.field_link', DRUPAL_REQUIRED);
  }

  /**
   * Tests the migration of text field instances with different text processing.
   */
  public function testTextFieldInstances() {
    // All text and text_long field instances using a field base that has only
    // plain text instances should be migrated to string and string_long fields.
    // All text_with_summary field instances using a field base that has only
    // plain text instances should not have been migrated since there's no such
    // thing as a string_with_summary field.
    $this->assertEntity('node.page.field_text_plain', 'Text plain', 'string', FALSE, FALSE);
    $this->assertEntity('node.article.field_text_plain', 'Text plain', 'string', FALSE, TRUE);
    $this->assertEntity('node.page.field_text_long_plain', 'Text long plain', 'string_long', FALSE, FALSE);
    $this->assertEntity('node.article.field_text_long_plain', 'Text long plain', 'string_long', FALSE, TRUE);
    $this->assertNull(FieldConfig::load('node.page.field_text_sum_plain'));
    $this->assertNull(FieldConfig::load('node.article.field_text_sum_plain'));

    // All text, text_long and text_with_summary field instances using a field
    // base that has only filtered text instances should be migrated to text,
    // text_long and text_with_summary fields.
    $this->assertEntity('node.page.field_text_filtered', 'Text filtered', 'text', FALSE, FALSE);
    $this->assertEntity('node.article.field_text_filtered', 'Text filtered', 'text', FALSE, TRUE);
    $this->assertEntity('node.page.field_text_long_filtered', 'Text long filtered', 'text_long', FALSE, FALSE);
    $this->assertEntity('node.article.field_text_long_filtered', 'Text long filtered', 'text_long', FALSE, TRUE);
    $this->assertEntity('node.page.field_text_sum_filtered', 'Text summary filtered', 'text_with_summary', FALSE, FALSE);
    $this->assertEntity('node.article.field_text_sum_filtered', 'Text summary filtered', 'text_with_summary', FALSE, TRUE);

    // All text, text_long and text_with_summary field instances using a field
    // base that has both plain text and filtered text instances should not have
    // been migrated.
    $this->assertNull(FieldConfig::load('node.page.field_text_plain_filtered'));
    $this->assertNull(FieldConfig::load('node.article.field_text_plain_filtered'));
    $this->assertNull(FieldConfig::load('node.page.field_text_long_plain_filtered'));
    $this->assertNull(FieldConfig::load('node.article.field_text_long_plain_filtered'));
    $this->assertNull(FieldConfig::load('node.page.field_text_sum_plain_filtered'));
    $this->assertNull(FieldConfig::load('node.article.field_text_sum_plain_filtered'));

    // For each text field instances that were skipped, there should be a log
    // message with the required steps to fix this.
    $migration = $this->getMigration('d7_field_instance');
    $messages = $migration->getIdMap()->getMessageIterator()->fetchAll();
    $errors = array_map(function ($message) {
      return $message->message;
    }, $messages);
    $this->assertCount(8, $errors);
    sort($errors);
    $message = 'Can\'t migrate source field field_text_long_plain_filtered configured with both plain text and filtered text processing. See https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#plain-text';
    $this->assertEquals($errors[0], $message);
    $this->assertEquals($errors[1], $message);
    $message = 'Can\'t migrate source field field_text_plain_filtered configured with both plain text and filtered text processing. See https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#plain-text';
    $this->assertEquals($errors[2], $message);
    $this->assertEquals($errors[3], $message);
    $message = 'Can\'t migrate source field field_text_sum_plain of type text_with_summary configured with plain text processing. See https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#plain-text';
    $this->assertEquals($errors[4], $message);
    $this->assertEquals($errors[5], $message);
    $message = 'Can\'t migrate source field field_text_sum_plain_filtered of type text_with_summary configured with plain text processing. See https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#plain-text';
    $this->assertEquals($errors[6], $message);
    $this->assertEquals($errors[7], $message);
  }

}
