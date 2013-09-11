<?php

/**
 * @file
 * Contains \Drupal\entity\Tests\EntityDisplayModeTest.
 */

namespace Drupal\entity\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the entity display mode configuration entities.
 */
class EntityDisplayModeTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test');

  public static function getInfo() {
    return array(
      'name' => 'Entity display modes UI',
      'description' => 'Tests the entity display modes UI.',
      'group' => 'Entity',
    );
  }

  /**
   * Tests the EntityViewMode user interface.
   */
  public function testEntityViewModeUI() {
    // Test the listing page.
    $this->drupalGet('admin/structure/display-modes/view');
    $this->assertResponse(403);
    $this->drupalLogin($this->drupalCreateUser(array('administer display modes')));
    $this->drupalGet('admin/structure/display-modes/view');
    $this->assertResponse(200);

    $this->drupalGet('admin/structure/display-modes/view/add/entity_test');
    $this->assertResponse(404);

    $this->drupalGet('admin/structure/display-modes/view/add');
    $this->assertNoLink(t('Test entity'), 'An entity type with no render controller cannot have view modes.');

    // Test adding a view mode.
    $this->clickLink(t('Test render entity'));
    $edit = array(
      'id' => strtolower($this->randomName()),
      'label' => $this->randomString(),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('Saved the %label view mode.', array('%label' => $edit['label'])));

    // Test editing the view mode.
    $this->drupalGet('admin/structure/display-modes/view/manage/entity_test_render.' . $edit['id']);

    // Test deleting the view mode.
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $this->assertRaw(t('Are you sure you want to delete the %label view mode?', array('%label' => $edit['label'])));
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $this->assertRaw(t('Deleted the %label view mode.', array('%label' => $edit['label'])));
  }

  /**
   * Tests the EntityFormMode user interface.
   */
  public function testEntityFormModeUI() {
    // Test the listing page.
    $this->drupalGet('admin/structure/display-modes/form');
    $this->assertResponse(403);
    $this->drupalLogin($this->drupalCreateUser(array('administer display modes')));
    $this->drupalGet('admin/structure/display-modes/form');
    $this->assertResponse(200);

    $this->drupalGet('admin/structure/display-modes/form/add/entity_test_render');
    $this->assertResponse(404);

    $this->drupalGet('admin/structure/display-modes/form/add');
    $this->assertNoLink(t('Test render entity'), 'An entity type with no form controller cannot have form modes.');

    // Test adding a form mode.
    $this->clickLink(t('Test entity'));
    $edit = array(
      'id' => strtolower($this->randomName()),
      'label' => $this->randomString(),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('Saved the %label form mode.', array('%label' => $edit['label'])));

    // Test editing the form mode.
    $this->drupalGet('admin/structure/display-modes/form/manage/entity_test.' . $edit['id']);

    // Test deleting the form mode.
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $this->assertRaw(t('Are you sure you want to delete the %label form mode?', array('%label' => $edit['label'])));
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $this->assertRaw(t('Deleted the %label form mode.', array('%label' => $edit['label'])));
  }

}
