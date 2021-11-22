<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d6;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests the migration of comment field instances from Drupal 6.
 *
 * @group comment
 * @group migrate_drupal_6
 */
class MigrateCommentFieldInstanceTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['comment']);
    $this->migrateContentTypes();
    $this->executeMigrations([
      'd6_comment_type',
      'd6_comment_field',
      'd6_comment_field_instance',
    ]);
  }

  /**
   * Asserts a comment field instance entity.
   *
   * @param string $bundle
   *   The bundle ID.
   * @param string $field_name
   *   The field name.
   * @param int $default_value
   *   The field's default_value setting.
   * @param int $default_mode
   *   The field's default_mode setting.
   * @param int $per_page
   *   The field's per_page setting.
   * @param int $anonymous
   *   The field's anonymous setting.
   * @param bool $form_location
   *   The field's form_location setting.
   * @param int $preview
   *   The field's preview setting.
   *
   * @internal
   */
  protected function assertEntity(string $bundle, string $field_name, int $default_value, int $default_mode, int $per_page, int $anonymous, bool $form_location, int $preview): void {
    $entity = FieldConfig::load("node.$bundle.$field_name");
    $this->assertInstanceOf(FieldConfig::class, $entity);
    $this->assertSame('node', $entity->getTargetEntityTypeId());
    $this->assertSame('Comments', $entity->label());
    $this->assertTrue($entity->isRequired());
    $this->assertSame($bundle, $entity->getTargetBundle());
    $this->assertSame($field_name, $entity->getFieldStorageDefinition()->getName());
    $this->assertSame($default_value, $entity->get('default_value')[0]['status']);
    $this->assertSame($default_mode, $entity->getSetting('default_mode'));
    $this->assertSame($per_page, $entity->getSetting('per_page'));
    $this->assertSame($anonymous, $entity->getSetting('anonymous'));
    $this->assertSame($form_location, $entity->getSetting('form_location'));
    $this->assertSame($preview, $entity->getSetting('preview'));
  }

  /**
   * Tests the migrated field instance values.
   */
  public function testMigration() {
    $this->assertEntity('article', 'comment_node_article', 2, 1, 50, 0, FALSE, 1);
    $this->assertEntity('company', 'comment_node_company', 2, 1, 50, 0, FALSE, 1);
    $this->assertEntity('employee', 'comment_node_employee', 2, 1, 50, 0, FALSE, 1);
    $this->assertEntity('event', 'comment_node_event', 2, 1, 50, 0, FALSE, 1);
    $this->assertEntity('forum', 'comment_forum', 2, 1, 50, 0, FALSE, 1);
    $this->assertEntity('page', 'comment_node_page', 0, 1, 50, 0, FALSE, 1);
    $this->assertEntity('sponsor', 'comment_node_sponsor', 2, 1, 50, 0, FALSE, 1);
    $this->assertEntity('story', 'comment_node_story', 2, 0, 70, 1, FALSE, 0);
    $this->assertEntity('test_event', 'comment_node_test_event', 2, 1, 50, 0, FALSE, 1);
    $this->assertEntity('test_page', 'comment_node_test_page', 2, 1, 50, 0, FALSE, 1);
    $this->assertEntity('test_planet', 'comment_node_test_planet', 2, 1, 50, 0, FALSE, 1);
    $this->assertEntity('test_story', 'comment_node_test_story', 2, 1, 50, 0, FALSE, 1);
  }

}
