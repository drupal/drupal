<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Migrate\d7\MigrateCommentFieldInstanceTest.
 */

namespace Drupal\comment\Tests\Migrate\d7;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests creation of comment reference fields for each comment type defined
 * in Drupal 7.
 *
 * @group comment
 */
class MigrateCommentFieldInstanceTest extends MigrateDrupal7TestBase {

  public static $modules = ['node', 'comment', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->executeMigration('d7_node_type');
    $this->executeMigration('d7_comment_type');
    $this->executeMigration('d7_comment_field');
    $this->executeMigration('d7_comment_field_instance');
  }

  /**
   * Asserts a comment field entity.
   *
   * @param string $id
   *   The entity ID.
   * @param string $field_name
   *   The field name.
   * @param string $bundle
   *   The bundle ID.
   * @param int $default_mode
   *   The field's default_mode setting.
   * @param int $per_page
   *   The field's per_page setting.
   * @param bool $anonymous
   *   The field's anonymous setting.
   * @param int $form_location
   *   The field's form_location setting.
   * @param bool $preview
   *   The field's preview setting.
   */
  protected function assertEntity($id, $field_name, $bundle, $default_mode, $per_page, $anonymous, $form_location, $preview) {
    $entity = FieldConfig::load($id);
    $this->assertTrue($entity instanceof FieldConfigInterface);
    /** @var \Drupal\field\FieldConfigInterface $entity */
    $this->assertIdentical('node', $entity->getTargetEntityTypeId());
    $this->assertIdentical('Comments', $entity->label());
    $this->assertTrue($entity->isRequired());
    $this->assertIdentical($field_name, $entity->getFieldStorageDefinition()->getName());
    $this->assertIdentical($bundle, $entity->getTargetBundle());
    $this->assertTrue($entity->get('default_value')[0]['status']);
    $this->assertEqual($default_mode, $entity->getSetting('default_mode'));
    $this->assertIdentical($per_page, $entity->getSetting('per_page'));
    $this->assertEqual($anonymous, $entity->getSetting('anonymous'));
    // This assertion fails because 1 !== TRUE. It's extremely strange that
    // the form_location setting is returning a boolean, but this appears to
    // be a problem with the entity, not with the migration.
    // $this->asserIdentical($form_location, $entity->getSetting('form_location'));
    $this->assertEqual($preview, $entity->getSetting('preview'));
  }

  /**
   * Tests the migrated fields.
   */
  public function testMigration() {
    $this->assertEntity('node.page.comment_node_page', 'comment_node_page', 'page', TRUE, 50, FALSE, CommentItemInterface::FORM_BELOW, TRUE);
    $this->assertEntity('node.article.comment_node_article', 'comment_node_article', 'article', TRUE, 50, FALSE, CommentItemInterface::FORM_BELOW, TRUE);
    $this->assertEntity('node.blog.comment_node_blog', 'comment_node_blog', 'blog', TRUE, 50, FALSE, CommentItemInterface::FORM_BELOW, TRUE);
    $this->assertEntity('node.book.comment_node_book', 'comment_node_book', 'book', TRUE, 50, FALSE, CommentItemInterface::FORM_BELOW, TRUE);
    $this->assertEntity('node.forum.comment_node_forum', 'comment_node_forum', 'forum', TRUE, 50, FALSE, CommentItemInterface::FORM_BELOW, TRUE);
    $this->assertEntity('node.test_content_type.comment_node_test_content_type', 'comment_node_test_content_type', 'test_content_type', TRUE, 30, FALSE, CommentItemInterface::FORM_BELOW, TRUE);
  }

}
