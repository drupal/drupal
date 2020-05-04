<?php

namespace Drupal\Tests\field\Kernel\Migrate\d7;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Migrate field widget settings.
 *
 * @group field
 */
class MigrateFieldInstanceWidgetSettingsTest extends MigrateDrupal7TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'comment',
    'datetime',
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
  protected function setUp() {
    parent::setUp();
    $this->migrateFields();
    $this->executeMigration('d7_field_instance_widget_settings');
  }

  /**
   * Asserts various aspects of a form display entity.
   *
   * @param string $id
   *   The entity ID.
   * @param string $expected_entity_type
   *   The expected entity type to which the display settings are attached.
   * @param string $expected_bundle
   *   The expected bundle to which the display settings are attached.
   */
  protected function assertEntity($id, $expected_entity_type, $expected_bundle) {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity */
    $entity = EntityFormDisplay::load($id);
    $this->assertInstanceOf(EntityFormDisplayInterface::class, $entity);
    $this->assertIdentical($expected_entity_type, $entity->getTargetEntityTypeId());
    $this->assertIdentical($expected_bundle, $entity->getTargetBundle());
  }

  /**
   * Asserts various aspects of a particular component of a form display.
   *
   * @param string $display_id
   *   The form display ID.
   * @param string $component_id
   *   The component ID.
   * @param string $widget_type
   *   The expected widget type.
   * @param string $weight
   *   The expected weight of the component.
   */
  protected function assertComponent($display_id, $component_id, $widget_type, $weight) {
    $component = EntityFormDisplay::load($display_id)->getComponent($component_id);
    $this->assertIsArray($component);
    $this->assertIdentical($widget_type, $component['type']);
    $this->assertIdentical($weight, $component['weight']);
  }

  /**
   * Test that migrated view modes can be loaded using D8 APIs.
   */
  public function testWidgetSettings() {
    $this->assertEntity('node.page.default', 'node', 'page');
    $this->assertComponent('node.page.default', 'body', 'text_textarea_with_summary', -4);
    $this->assertComponent('node.page.default', 'field_text_plain', 'string_textfield', -2);
    $this->assertComponent('node.page.default', 'field_text_filtered', 'text_textfield', 0);
    $this->assertComponent('node.page.default', 'field_text_long_plain', 'string_textarea', 4);
    $this->assertComponent('node.page.default', 'field_text_long_filtered', 'text_textarea', 6);
    $this->assertComponent('node.page.default', 'field_text_sum_filtered', 'text_textarea_with_summary', 12);

    $this->assertEntity('node.article.default', 'node', 'article');
    $this->assertComponent('node.article.default', 'body', 'text_textarea_with_summary', -4);
    $this->assertComponent('node.article.default', 'field_tags', 'entity_reference_autocomplete', -4);
    $this->assertComponent('node.article.default', 'field_image', 'image_image', -1);
    $this->assertComponent('node.article.default', 'field_text_plain', 'string_textfield', 11);
    $this->assertComponent('node.article.default', 'field_text_filtered', 'text_textfield', 12);
    $this->assertComponent('node.article.default', 'field_text_long_plain', 'string_textarea', 14);
    $this->assertComponent('node.article.default', 'field_text_long_filtered', 'text_textarea', 15);
    $this->assertComponent('node.article.default', 'field_text_sum_filtered', 'text_textarea_with_summary', 18);

    $this->assertEntity('node.blog.default', 'node', 'blog');
    $this->assertComponent('node.blog.default', 'body', 'text_textarea_with_summary', -4);

    $this->assertEntity('node.book.default', 'node', 'book');
    $this->assertComponent('node.book.default', 'body', 'text_textarea_with_summary', -4);

    $this->assertEntity('node.forum.default', 'node', 'forum');
    $this->assertComponent('node.forum.default', 'body', 'text_textarea_with_summary', 1);
    $this->assertComponent('node.forum.default', 'taxonomy_forums', 'options_select', 0);

    $this->assertEntity('node.test_content_type.default', 'node', 'test_content_type');
    $this->assertComponent('node.test_content_type.default', 'field_boolean', 'boolean_checkbox', 1);
    $this->assertComponent('node.test_content_type.default', 'field_date', 'datetime_default', 2);
    $this->assertComponent('node.test_content_type.default', 'field_date_with_end_time', 'datetime_default', 3);
    $this->assertComponent('node.test_content_type.default', 'field_email', 'email_default', 4);
    $this->assertComponent('node.test_content_type.default', 'field_file', 'file_generic', 5);
    $this->assertComponent('node.test_content_type.default', 'field_float', 'number', 7);
    $this->assertComponent('node.test_content_type.default', 'field_images', 'image_image', 8);
    $this->assertComponent('node.test_content_type.default', 'field_integer', 'number', 9);
    $this->assertComponent('node.test_content_type.default', 'field_link', 'link_default', 10);
    $this->assertComponent('node.test_content_type.default', 'field_integer_list', 'options_buttons', 12);
    $this->assertComponent('node.test_content_type.default', 'field_long_text', 'text_textarea_with_summary', 13);
    $this->assertComponent('node.test_content_type.default', 'field_phone', 'telephone_default', 6);
    $this->assertComponent('node.test_content_type.default', 'field_term_reference', 'entity_reference_autocomplete', 14);
    $this->assertComponent('node.test_content_type.default', 'field_node_entityreference', 'entity_reference_autocomplete', 16);
    $this->assertComponent('node.test_content_type.default', 'field_user_entityreference', 'options_buttons', 17);
    $this->assertComponent('node.test_content_type.default', 'field_term_entityreference', 'entity_reference_autocomplete_tags', 18);
    $this->assertComponent('node.test_content_type.default', 'field_text', 'string_textfield', 15);
    $this->assertComponent('node.test_content_type.default', 'field_text_list', 'options_select', 11);
    $this->assertComponent('node.test_content_type.default', 'field_float_list', 'options_select', 20);

    $this->assertEntity('user.user.default', 'user', 'user');
    $this->assertComponent('user.user.default', 'field_file', 'file_generic', 8);

    $this->assertEntity('comment.comment_node_test_content_type.default', 'comment', 'comment_node_test_content_type');
    $this->assertComponent('comment.comment_node_test_content_type.default', 'comment_body', 'text_textarea', 0);
    $this->assertComponent('comment.comment_node_test_content_type.default', 'field_integer', 'number', 2);

    $this->assertEntity('taxonomy_term.test_vocabulary.default', 'taxonomy_term', 'test_vocabulary');
    $this->assertComponent('comment.comment_node_test_content_type.default', 'field_integer', 'number', 2);
  }

}
