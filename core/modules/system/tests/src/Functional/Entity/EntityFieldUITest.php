<?php

namespace Drupal\Tests\system\Functional\Entity;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that while adding fields to entity types entity which doesn't have id
 * shouldn't appear.
 *
 * @group entity
 */
class EntityFieldUITest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'node', 'field_ui', 'field'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->rootUser);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Tests the add page for an entity type using bundle entities.
   */
  public function testAddEntityReferenceField() {
    $this->drupalGet('admin/structure/types/manage/article/fields/add-field');
    $edit = [
      'new_storage_type' => 'entity_reference',
      'label' => 'Test Field',
      'field_name' => 'test_reference_field',
    ];
    $this->submitForm($edit, 'Save and continue');
    $this->assertSession()->optionNotExists('settings[target_type]', 'entity_test_no_id');
  }

}
