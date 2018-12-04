<?php

namespace Drupal\Tests\field\Kernel\Migrate\d7;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of D7 field formatter settings.
 *
 * @group field
 */
class MigrateFieldFormatterSettingsTest extends MigrateDrupal7TestBase {

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
    $this->executeMigrations([
      'd7_view_modes',
      'd7_field_formatter_settings',
    ]);
  }

  /**
   * Asserts various aspects of a view display.
   *
   * @param string $id
   *   The view display ID.
   */
  protected function assertEntity($id) {
    $display = EntityViewDisplay::load($id);
    $this->assertTrue($display instanceof EntityViewDisplayInterface);
  }

  /**
   * Asserts various aspects of a particular component of a view display.
   *
   * @param string $display_id
   *   The view display ID.
   * @param string $component_id
   *   The component ID.
   * @param string $type
   *   The expected component type (formatter plugin ID).
   * @param string $label
   *   The expected label of the component.
   * @param int $weight
   *   The expected weight of the component.
   */
  protected function assertComponent($display_id, $component_id, $type, $label, $weight) {
    $component = EntityViewDisplay::load($display_id)->getComponent($component_id);
    $this->assertTrue(is_array($component));
    $this->assertIdentical($type, $component['type']);
    $this->assertIdentical($label, $component['label']);
    $this->assertIdentical($weight, $component['weight']);
  }

  /**
   * Asserts that a particular component is NOT included in a display.
   *
   * @param string $display_id
   *   The display ID.
   * @param string $component_id
   *   The component ID.
   */
  protected function assertComponentNotExists($display_id, $component_id) {
    $component = EntityViewDisplay::load($display_id)->getComponent($component_id);
    $this->assertTrue(is_null($component));
  }

  /**
   * Tests migration of D7 field formatter settings.
   */
  public function testMigration() {
    $this->assertEntity('comment.comment_node_article.default');
    $this->assertComponent('comment.comment_node_article.default', 'comment_body', 'text_default', 'hidden', 0);

    $this->assertEntity('comment.comment_node_blog.default');
    $this->assertComponent('comment.comment_node_blog.default', 'comment_body', 'text_default', 'hidden', 0);

    $this->assertEntity('comment.comment_node_book.default');
    $this->assertComponent('comment.comment_node_book.default', 'comment_body', 'text_default', 'hidden', 0);

    $this->assertEntity('comment.comment_forum.default');
    $this->assertComponent('comment.comment_forum.default', 'comment_body', 'text_default', 'hidden', 0);

    $this->assertEntity('comment.comment_node_page.default');
    $this->assertComponent('comment.comment_node_page.default', 'comment_body', 'text_default', 'hidden', 0);

    $this->assertEntity('comment.comment_node_test_content_type.default');
    $this->assertComponent('comment.comment_node_test_content_type.default', 'comment_body', 'text_default', 'hidden', 0);
    $this->assertComponent('comment.comment_node_test_content_type.default', 'field_integer', 'number_integer', 'above', 1);

    $this->assertEntity('node.article.default');
    $this->assertComponent('node.article.default', 'body', 'text_default', 'hidden', 0);
    $this->assertComponent('node.article.default', 'field_tags', 'entity_reference_label', 'above', 10);
    $this->assertComponent('node.article.default', 'field_image', 'image', 'hidden', -1);
    $this->assertComponent('node.article.default', 'field_text_plain', 'string', 'above', 11);
    $this->assertComponent('node.article.default', 'field_text_filtered', 'text_default', 'above', 12);
    $this->assertComponent('node.article.default', 'field_text_long_plain', 'basic_string', 'above', 14);
    $this->assertComponent('node.article.default', 'field_text_long_filtered', 'text_default', 'above', 15);
    $this->assertComponent('node.article.default', 'field_text_sum_filtered', 'text_default', 'above', 18);

    $this->assertEntity('node.article.teaser');
    $this->assertComponent('node.article.teaser', 'body', 'text_summary_or_trimmed', 'hidden', 0);
    $this->assertComponent('node.article.teaser', 'field_tags', 'entity_reference_label', 'above', 10);
    $this->assertComponent('node.article.teaser', 'field_image', 'image', 'hidden', -1);

    $this->assertEntity('node.blog.default');
    $this->assertComponent('node.blog.default', 'body', 'text_default', 'hidden', 0);

    $this->assertEntity('node.blog.teaser');
    $this->assertComponent('node.blog.teaser', 'body', 'text_summary_or_trimmed', 'hidden', 0);

    $this->assertEntity('node.book.default');
    $this->assertComponent('node.book.default', 'body', 'text_default', 'hidden', 0);

    $this->assertEntity('node.book.teaser');
    $this->assertComponent('node.book.teaser', 'body', 'text_summary_or_trimmed', 'hidden', 0);

    $this->assertEntity('node.forum.default');
    $this->assertComponent('node.forum.default', 'body', 'text_default', 'hidden', 11);
    $this->assertComponent('node.forum.default', 'taxonomy_forums', 'entity_reference_label', 'above', 10);

    $this->assertEntity('node.forum.teaser');
    $this->assertComponent('node.forum.teaser', 'body', 'text_summary_or_trimmed', 'hidden', 11);
    $this->assertComponent('node.forum.teaser', 'taxonomy_forums', 'entity_reference_label', 'above', 10);

    $this->assertEntity('node.page.default');
    $this->assertComponent('node.page.default', 'body', 'text_default', 'hidden', 0);
    $this->assertComponent('node.page.default', 'field_text_plain', 'string', 'above', 1);
    $this->assertComponent('node.page.default', 'field_text_filtered', 'text_default', 'above', 2);
    $this->assertComponent('node.page.default', 'field_text_long_plain', 'basic_string', 'above', 4);
    $this->assertComponent('node.page.default', 'field_text_long_filtered', 'text_default', 'above', 5);
    $this->assertComponent('node.page.default', 'field_text_sum_filtered', 'text_default', 'above', 8);

    $this->assertEntity('node.page.teaser');
    $this->assertComponent('node.page.teaser', 'body', 'text_summary_or_trimmed', 'hidden', 0);

    $this->assertEntity('node.test_content_type.default');
    $this->assertComponent('node.test_content_type.default', 'field_boolean', 'list_default', 'above', 0);
    $this->assertComponent('node.test_content_type.default', 'field_email', 'email_mailto', 'above', 1);
    // Phone formatters are not mapped and should default to basic_string.
    $this->assertComponent('node.test_content_type.default', 'field_phone', 'basic_string', 'above', 2);
    $this->assertComponent('node.test_content_type.default', 'field_date', 'datetime_default', 'above', 3);
    $this->assertComponent('node.test_content_type.default', 'field_date_with_end_time', 'datetime_default', 'above', 4);
    $this->assertComponent('node.test_content_type.default', 'field_file', 'file_default', 'above', 5);
    $this->assertComponent('node.test_content_type.default', 'field_float', 'number_decimal', 'above', 6);
    $this->assertComponent('node.test_content_type.default', 'field_images', 'image', 'above', 7);
    $this->assertComponent('node.test_content_type.default', 'field_integer', 'number_integer', 'above', 8);
    $this->assertComponent('node.test_content_type.default', 'field_link', 'link', 'above', 9);
    $this->assertComponent('node.test_content_type.default', 'field_text_list', 'list_default', 'above', 10);
    $this->assertComponent('node.test_content_type.default', 'field_integer_list', 'list_default', 'above', 11);
    $this->assertComponent('node.test_content_type.default', 'field_float_list', 'list_default', 'above', 19);
    $this->assertComponent('node.test_content_type.default', 'field_long_text', 'text_default', 'above', 12);
    $this->assertComponent('node.test_content_type.default', 'field_node_entityreference', 'entity_reference_label', 'above', 15);
    $this->assertComponent('node.test_content_type.default', 'field_user_entityreference', 'entity_reference_label', 'above', 16);
    $this->assertComponent('node.test_content_type.default', 'field_term_entityreference', 'entity_reference_label', 'above', 17);
    $this->assertComponentNotExists('node.test_content_type.default', 'field_term_reference');
    $this->assertComponentNotExists('node.test_content_type.default', 'field_text');

    $this->assertEntity('user.user.default');
    $this->assertComponent('user.user.default', 'field_file', 'file_default', 'above', 0);
  }

}
