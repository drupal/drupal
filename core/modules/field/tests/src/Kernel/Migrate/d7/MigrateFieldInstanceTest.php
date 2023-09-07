<?php

namespace Drupal\Tests\field\Kernel\Migrate\d7;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Migrates Drupal 7 field instances.
 *
 * @group field
 */
class MigrateFieldInstanceTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'datetime',
    'datetime_range',
    'image',
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
    $this->migrateFields();
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
   *
   * @internal
   */
  protected function assertEntity(string $id, string $expected_label, string $expected_field_type, bool $is_required, bool $expected_translatable): void {
    [$expected_entity_type, $expected_bundle, $expected_name] = explode('.', $id);

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
   * @param string $id
   *   The entity ID in the form ENTITY_TYPE.BUNDLE.FIELD_NAME.
   * @param int $title_setting
   *   The expected title setting.
   *
   * @internal
   */
  protected function assertLinkFields(string $id, int $title_setting): void {
    $field = FieldConfig::load($id);
    $this->assertSame($title_setting, $field->getSetting('title'));
  }

  /**
   * Asserts the settings of an entity reference field config entity.
   *
   * @param string $id
   *   The entity ID in the form ENTITY_TYPE.BUNDLE.FIELD_NAME.
   * @param string[] $target_bundles
   *   An array of expected target bundles.
   *
   * @internal
   */
  protected function assertEntityReferenceFields(string $id, array $target_bundles): void {
    $field = FieldConfig::load($id);
    $handler_settings = $field->getSetting('handler_settings');
    $this->assertArrayHasKey('target_bundles', $handler_settings);
    foreach ($handler_settings['target_bundles'] as $target_bundle) {
      $this->assertContains($target_bundle, $target_bundles);
    }
  }

  /**
   * Tests migrating D7 field instances to field_config entities.
   */
  public function testFieldInstances() {
    $this->assertEntity('comment.comment_node_page.comment_body', 'Comment', 'text_long', TRUE, FALSE);
    $this->assertEntity('node.page.body', 'Body', 'text_with_summary', FALSE, FALSE);
    $this->assertEntity('comment.comment_node_article.comment_body', 'Comment', 'text_long', TRUE, FALSE);
    $this->assertEntity('node.article.body', 'Body', 'text_with_summary', FALSE, TRUE);
    $this->assertEntity('node.article.field_tags', 'Tags', 'entity_reference', FALSE, FALSE);
    $this->assertEntity('node.article.field_image', 'Image', 'image', FALSE, TRUE);
    $this->assertEntity('comment.comment_node_blog.comment_body', 'Comment', 'text_long', TRUE, FALSE);
    $this->assertEntity('node.blog.body', 'Body', 'text_with_summary', FALSE, TRUE);
    $this->assertEntity('node.blog.field_file_mfw', 'file_mfw', 'file', FALSE, TRUE);
    $this->assertEntity('node.blog.field_image_miw', 'image_miw', 'image', FALSE, TRUE);
    $this->assertEntity('comment.comment_node_book.comment_body', 'Comment', 'text_long', TRUE, FALSE);
    $this->assertEntity('node.book.body', 'Body', 'text_with_summary', FALSE, FALSE);
    $this->assertEntity('node.forum.taxonomy_forums', 'Forums', 'entity_reference', TRUE, FALSE);
    $this->assertEntity('comment.comment_forum.comment_body', 'Comment', 'text_long', TRUE, FALSE);
    $this->assertEntity('node.forum.body', 'Body', 'text_with_summary', FALSE, FALSE);
    $this->assertEntity('node.forum.field_event', 'event', 'daterange', FALSE, FALSE);
    $this->assertEntity('comment.comment_node_test_content_type.comment_body', 'Comment', 'text_long', TRUE, FALSE);
    $this->assertEntity('node.test_content_type.field_boolean', 'Boolean', 'boolean', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_email', 'Email', 'email', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_phone', 'Phone', 'telephone', TRUE, FALSE);
    $this->assertEntity('node.test_content_type.field_date', 'Date', 'datetime', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_date_with_end_time', 'Date With End Time', 'timestamp', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_file', 'File', 'file', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_float', 'Float', 'float', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_images', 'Images', 'image', TRUE, FALSE);
    $this->assertEntity('node.test_content_type.field_integer', 'Integer', 'integer', TRUE, TRUE);
    $this->assertEntity('node.test_content_type.field_link', 'Link', 'link', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_text_list', 'Text List', 'list_string', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_integer_list', 'Integer List', 'list_integer', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_float_list', 'Float List', 'list_float', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_long_text', 'Long text', 'text_with_summary', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_term_reference', 'Term Reference', 'entity_reference', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_text', 'Text', 'string', FALSE, FALSE);
    $this->assertEntity('node.test_content_type.field_telephone', 'Telephone', 'telephone', FALSE, FALSE);
    $this->assertEntity('comment.comment_node_test_content_type.field_integer', 'Integer', 'integer', FALSE, TRUE);
    $this->assertEntity('comment.comment_node_a_thirty_two_char.comment_body', 'Comment', 'text_long', TRUE, FALSE);
    $this->assertEntity('user.user.field_file', 'File', 'file', FALSE, FALSE);

    $this->assertLinkFields('node.test_content_type.field_link', DRUPAL_OPTIONAL);
    $this->assertLinkFields('node.article.field_link', DRUPAL_DISABLED);
    $this->assertLinkFields('node.blog.field_link', DRUPAL_REQUIRED);

    $this->assertEntityReferenceFields('node.article.field_tags', ['tags']);
    $this->assertEntityReferenceFields('node.forum.taxonomy_forums', ['sujet_de_discussion']);
    $this->assertEntityReferenceFields('node.test_content_type.field_term_reference', ['tags', 'test_vocabulary']);

    // Tests that fields created by the Title module are not migrated.
    $title_field = FieldConfig::load('node.test_content_type.title_field');
    $this->assertNull($title_field);
    $subject_field = FieldConfig::load('comment.comment_node_article.subject_field');
    $this->assertNull($subject_field);
    $name_field = FieldConfig::load('taxonomy_term.test_vocabulary.name_field');
    $this->assertNull($name_field);
    $description_field = FieldConfig::load('taxonomy_term.test_vocabulary.description_field');
    $this->assertNull($description_field);

    $boolean_field = FieldConfig::load('node.test_content_type.field_boolean');
    $expected_settings = [
      'on_label' => '1',
      'off_label' => 'Off',
    ];
    $this->assertSame($expected_settings, $boolean_field->get('settings'));

    // Test a synchronized field is not translatable.
    $field = FieldConfig::load('node.article.field_text_plain');
    $this->assertInstanceOf(FieldConfig::class, $field);
    $this->assertFalse($field->isTranslatable());

    // Test the translation settings for taxonomy fields.
    $this->assertEntity('node.article.field_vocab_fixed', 'vocab_fixed', 'entity_reference', FALSE, TRUE);
    $this->assertEntity('node.article.field_vocab_localize', 'vocab_localize', 'entity_reference', FALSE, FALSE);
    $this->assertEntity('node.article.field_vocab_translate', 'vocab_translate', 'entity_reference', FALSE, TRUE);

    // Test the node and user reference fields.
    $this->assertEntity('node.article.field_node_reference', 'Node Reference', 'entity_reference', FALSE, TRUE);
    $this->assertEntity('node.article.field_user_reference', 'User Reference', 'entity_reference', FALSE, TRUE);
    $expected_handler_settings = [
      'sort' => [
        'field' => '_none',
        'direction' => 'ASC',
      ],
      'auto_create' => FALSE,
      'filter' => [
        'type' => 'role',
        'role' => [
          'authenticated user' => 'authenticated user',
        ],
      ],
      'include_anonymous' => TRUE,
    ];
    $field = FieldConfig::load('node.article.field_user_reference');
    $actual = $field->getSetting('handler_settings');
    $this->assertSame($expected_handler_settings, $actual);

    // Test migration of text field instances with different text processing.
    // All text and text_long field instances using a field base that has only
    // plain text instances should be migrated to string and string_long fields.
    // All text_with_summary field instances using a field base that has only
    // plain text instances should not have been migrated since there's no such
    // thing as a string_with_summary field.
    $this->assertEntity('node.page.field_text_plain', 'Text plain', 'string', FALSE, FALSE);
    $this->assertEntity('node.article.field_text_plain', 'Text plain', 'string', FALSE, FALSE);
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
    $errors = array_map(function ($message) {
      return $message->message;
    }, iterator_to_array($migration->getIdMap()->getMessages()));
    $this->assertCount(8, $errors);
    sort($errors);
    $message = 'd7_field_instance:type: Can\'t migrate source field field_text_long_plain_filtered configured with both plain text and filtered text processing. See https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#plain-text';
    $this->assertEquals($errors[0], $message);
    $this->assertEquals($errors[1], $message);
    $message = 'd7_field_instance:type: Can\'t migrate source field field_text_plain_filtered configured with both plain text and filtered text processing. See https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#plain-text';
    $this->assertEquals($errors[2], $message);
    $this->assertEquals($errors[3], $message);
    $message = 'd7_field_instance:type: Can\'t migrate source field field_text_sum_plain of type text_with_summary configured with plain text processing. See https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#plain-text';
    $this->assertEquals($errors[4], $message);
    $this->assertEquals($errors[5], $message);
    $message = 'd7_field_instance:type: Can\'t migrate source field field_text_sum_plain_filtered of type text_with_summary configured with plain text processing. See https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#plain-text';
    $this->assertEquals($errors[6], $message);
    $this->assertEquals($errors[7], $message);
  }

}
