<?php

namespace Drupal\Tests\field_ui\Functional;

use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the entity display modes UI.
 *
 * @group field_ui
 */
class EntityDisplayModeTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = ['block', 'entity_test', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests the EntityViewMode user interface.
   */
  public function testEntityViewModeUI() {
    // Test the listing page.
    $this->drupalGet('admin/structure/display-modes/view');
    $this->assertResponse(403);
    $this->drupalLogin($this->drupalCreateUser(['administer display modes']));
    $this->drupalGet('admin/structure/display-modes/view');
    $this->assertResponse(200);
    $this->assertText(t('Add view mode'));
    $this->assertLinkByHref('admin/structure/display-modes/view/add');
    $this->assertLinkByHref('admin/structure/display-modes/view/add/entity_test');

    $this->drupalGet('admin/structure/display-modes/view/add/entity_test_mulrev');
    $this->assertResponse(404);

    $this->drupalGet('admin/structure/display-modes/view/add');
    $this->assertNoLink(t('Test entity - revisions and data table'), 'An entity type with no view builder cannot have view modes.');

    // Test adding a view mode including dots in machine_name.
    $this->clickLink(t('Test entity'));
    $edit = [
      'id' => strtolower($this->randomMachineName()) . '.' . strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw('The machine-readable name must contain only lowercase letters, numbers, and underscores.');

    // Test adding a view mode.
    $edit = [
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('Saved the %label view mode.', ['%label' => $edit['label']]));

    // Test editing the view mode.
    $this->drupalGet('admin/structure/display-modes/view/manage/entity_test.' . $edit['id']);

    // Test that the link templates added by field_ui_entity_type_build() are
    // generating valid routes.
    $view_mode = EntityViewMode::load('entity_test.' . $edit['id']);
    $this->assertEquals(Url::fromRoute('entity.entity_view_mode.collection')->toString(), $view_mode->toUrl('collection')->toString());
    $this->assertEquals(Url::fromRoute('entity.entity_view_mode.add_form', ['entity_type_id' => $view_mode->getTargetType()])->toString(), $view_mode->toUrl('add-form')->toString());
    $this->assertEquals(Url::fromRoute('entity.entity_view_mode.edit_form', ['entity_view_mode' => $view_mode->id()])->toString(), $view_mode->toUrl('edit-form')->toString());
    $this->assertEquals(Url::fromRoute('entity.entity_view_mode.delete_form', ['entity_view_mode' => $view_mode->id()])->toString(), $view_mode->toUrl('delete-form')->toString());

    // Test deleting the view mode.
    $this->clickLink(t('Delete'));
    $this->assertRaw(t('Are you sure you want to delete the view mode %label?', ['%label' => $edit['label']]));
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $this->assertRaw(t('The view mode %label has been deleted.', ['%label' => $edit['label']]));
  }

  /**
   * Tests the EntityFormMode user interface.
   */
  public function testEntityFormModeUI() {
    // Test the listing page.
    $this->drupalGet('admin/structure/display-modes/form');
    $this->assertResponse(403);
    $this->drupalLogin($this->drupalCreateUser(['administer display modes']));
    $this->drupalGet('admin/structure/display-modes/form');
    $this->assertResponse(200);
    $this->assertText(t('Add form mode'));
    $this->assertLinkByHref('admin/structure/display-modes/form/add');

    $this->drupalGet('admin/structure/display-modes/form/add/entity_test_no_label');
    $this->assertResponse(404);

    $this->drupalGet('admin/structure/display-modes/form/add');
    $this->assertNoLink(t('Entity Test without label'), 'An entity type with no form cannot have form modes.');

    // Test adding a view mode including dots in machine_name.
    $this->clickLink(t('Test entity'));
    $edit = [
      'id' => strtolower($this->randomMachineName()) . '.' . strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw('The machine-readable name must contain only lowercase letters, numbers, and underscores.');

    // Test adding a form mode.
    $edit = [
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('Saved the %label form mode.', ['%label' => $edit['label']]));

    // Test editing the form mode.
    $this->drupalGet('admin/structure/display-modes/form/manage/entity_test.' . $edit['id']);

    // Test that the link templates added by field_ui_entity_type_build() are
    // generating valid routes.
    $form_mode = EntityFormMode::load('entity_test.' . $edit['id']);
    $this->assertEquals(Url::fromRoute('entity.entity_form_mode.collection')->toString(), $form_mode->toUrl('collection')->toString());
    $this->assertEquals(Url::fromRoute('entity.entity_form_mode.add_form', ['entity_type_id' => $form_mode->getTargetType()])->toString(), $form_mode->toUrl('add-form')->toString());
    $this->assertEquals(Url::fromRoute('entity.entity_form_mode.edit_form', ['entity_form_mode' => $form_mode->id()])->toString(), $form_mode->toUrl('edit-form')->toString());
    $this->assertEquals(Url::fromRoute('entity.entity_form_mode.delete_form', ['entity_form_mode' => $form_mode->id()])->toString(), $form_mode->toUrl('delete-form')->toString());

    // Test deleting the form mode.
    $this->clickLink(t('Delete'));
    $this->assertRaw(t('Are you sure you want to delete the form mode %label?', ['%label' => $edit['label']]));
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $this->assertRaw(t('The form mode %label has been deleted.', ['%label' => $edit['label']]));
  }

}
