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
    $this->assertText(t('Add new view mode'));
    $this->assertLinkByHref('admin/structure/display-modes/view/add');

    $this->drupalGet('admin/structure/display-modes/view/add/entity_test_mulrev');
    $this->assertResponse(404);

    $this->drupalGet('admin/structure/display-modes/view/add');
    $this->assertNoLink(t('Test entity - revisions and data table'), 'An entity type with no view builder cannot have view modes.');

    // Test adding a view mode including dots in machine_name.
    $this->clickLink(t('Test entity'));
    $edit = array(
      'id' => strtolower($this->randomName()) . '.' . strtolower($this->randomName()),
      'label' => $this->randomString(),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw('The machine-readable name must contain only lowercase letters, numbers, and underscores.');

    // Test adding a view mode.
    $edit = array(
      'id' => strtolower($this->randomName()),
      'label' => $this->randomString(),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('Saved the %label view mode.', array('%label' => $edit['label'])));

    // Test editing the view mode.
    $this->drupalGet('admin/structure/display-modes/view/manage/entity_test.' . $edit['id']);

    // Test deleting the view mode.
    $this->clickLink(t('Delete'));
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
    $this->assertText(t('Add new form mode'));
    $this->assertLinkByHref('admin/structure/display-modes/form/add');

    $this->drupalGet('admin/structure/display-modes/form/add/entity_test_no_label');
    $this->assertResponse(404);

    $this->drupalGet('admin/structure/display-modes/form/add');
    $this->assertNoLink(t('Entity Test without label'), 'An entity type with no form cannot have form modes.');

    // Test adding a view mode including dots in machine_name.
    $this->clickLink(t('Test entity'));
    $edit = array(
      'id' => strtolower($this->randomName()) . '.' . strtolower($this->randomName()),
      'label' => $this->randomString(),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw('The machine-readable name must contain only lowercase letters, numbers, and underscores.');

    // Test adding a form mode.
    $edit = array(
      'id' => strtolower($this->randomName()),
      'label' => $this->randomString(),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('Saved the %label form mode.', array('%label' => $edit['label'])));

    // Test editing the form mode.
    $this->drupalGet('admin/structure/display-modes/form/manage/entity_test.' . $edit['id']);

    // Test deleting the form mode.
    $this->clickLink(t('Delete'));
    $this->assertRaw(t('Are you sure you want to delete the %label form mode?', array('%label' => $edit['label'])));
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $this->assertRaw(t('Deleted the %label form mode.', array('%label' => $edit['label'])));
  }

}
