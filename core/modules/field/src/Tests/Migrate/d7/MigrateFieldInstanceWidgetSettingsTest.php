<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Migrate\d7\MigrateFieldInstanceWidgetSettingsTest.
 */

namespace Drupal\field\Tests\Migrate\d7;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;
use Drupal\node\Entity\NodeType;

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
  public static $modules = array(
    'field',
    'telephone',
    'link',
    'file',
    'image',
    'datetime',
    'node',
    'text',
  );

  /**
   * Creates a node type.
   *
   * @param string $id
   *   The node type ID.
   */
  protected function createNodeType($id) {
    NodeType::create(array(
      'type' => $id,
      'label' => $this->randomString(),
    ))->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');

    $this->createNodeType('page');
    $this->createNodeType('article');
    $this->createNodeType('blog');
    $this->createNodeType('book');
    $this->createNodeType('forum');
    $this->createNodeType('test_content_type');

    // Add some id mappings for the dependent migrations.
    $id_mappings = [
      'd7_field' => [
        [['comment', 'comment_body'], ['comment', 'comment_body']],
        [['node', 'body'], ['node', 'body']],
        [['node', 'field_tags'], ['node', 'field_tags']],
        [['node', 'field_image'], ['node', 'field_image']],
        [['node', 'taxonomy_forums'], ['node', 'taxonomy_forums']],
        [['node', 'field_boolean'], ['node', 'field_boolean']],
        [['node', 'field_email'], ['node', 'field_email']],
        [['node', 'field_phone'], ['node', 'field_phone']],
        [['node', 'field_date'], ['node', 'field_date']],
        [['node', 'field_date_with_end_time'], ['node', 'field_date_with_end_time']],
        [['node', 'field_file'], ['node', 'field_file']],
        [['node', 'field_float'], ['node', 'field_float']],
        [['node', 'field_images'], ['node', 'field_images']],
        [['node', 'field_integer'], ['node', 'field_integer']],
        [['node', 'field_link'], ['node', 'field_link']],
        [['node', 'field_text_list'], ['node', 'field_text_list']],
        [['node', 'field_integer_list'], ['node', 'field_integer_list']],
        [['node', 'field_long_text'], ['node', 'field_long_text']],
        [['node', 'field_term_reference'], ['node', 'field_term_reference']],
        [['node', 'field_text'], ['node', 'field_text']],
        [['node', 'field_integer'], ['node', 'field_integer']],
        [['user', 'field_file'], ['user', 'field_file']],
      ],
      // We don't actually need any ID lookups from the d7_field_instance
      // migration -- it's merely a sensible dependency.
      'd7_field_instance' => [],
    ];
    $this->prepareMigrations($id_mappings);
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
    $this->assertTrue($entity instanceof EntityFormDisplayInterface);
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
    $this->assertTrue(is_array($component));
    $this->assertIdentical($widget_type, $component['type']);
    $this->assertIdentical($weight, $component['weight']);
  }

  /**
   * Test that migrated view modes can be loaded using D8 APIs.
   */
  public function testWidgetSettings() {
    $this->assertEntity('node.page.default', 'node', 'page');
    $this->assertComponent('node.page.default', 'body', 'text_textarea_with_summary', -4);

    $this->assertEntity('node.article.default', 'node', 'article');
    $this->assertComponent('node.article.default', 'body', 'text_textarea_with_summary', -4);
    $this->assertComponent('node.article.default', 'field_tags', 'taxonomy_autocomplete', -4);
    $this->assertComponent('node.article.default', 'field_image', 'image_image', -1);

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
    $this->assertComponent('node.test_content_type.default', 'field_term_reference', 'taxonomy_autocomplete', 14);
    $this->assertComponent('node.test_content_type.default', 'field_text', 'text_textfield', 15);
    $this->assertComponent('node.test_content_type.default', 'field_text_list', 'options_select', 11);
  }

}
