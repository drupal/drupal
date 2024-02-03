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
  protected static $modules = ['block', 'entity_test', 'field_ui', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a node type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests the EntityViewMode user interface.
   */
  public function testEntityViewModeUI() {
    // Test the listing page.
    $this->drupalGet('admin/structure/display-modes/view');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->drupalCreateUser(['administer display modes']));
    $this->drupalGet('admin/structure/display-modes/view');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Add view mode');
    $this->assertSession()->linkByHrefExists('admin/structure/display-modes/view/add');
    $this->assertSession()->linkByHrefExists('admin/structure/display-modes/view/add/entity_test');

    $this->drupalGet('admin/structure/display-modes/view/add/entity_test_mulrev');
    $this->assertSession()->statusCodeEquals(404);

    $this->drupalGet('admin/structure/display-modes/view/add');
    $this->assertSession()->linkNotExists('Test entity - revisions and data table', 'An entity type with no view builder cannot have view modes.');

    // Test adding a view mode including dots in machine_name.
    $this->clickLink('Test entity');
    // Check if 'Name' field is required.
    $this->assertTrue($this->getSession()->getPage()->findField('label')->hasClass('required'));
    $edit = [
      'id' => $this->randomMachineName() . '.' . $this->randomMachineName(),
      'label' => $this->randomString(),
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name must contain only lowercase letters, numbers, and underscores.');

    // Test adding a view mode.
    $edit = [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("Saved the {$edit['label']} view mode.");

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
    $this->clickLink('Delete');
    $this->assertSession()->pageTextContains("Are you sure you want to delete the view mode {$edit['label']}?");
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains("The view mode {$edit['label']} has been deleted.");
  }

  /**
   * Tests the EntityFormMode user interface.
   */
  public function testEntityFormModeUI() {
    // Test the listing page.
    $this->drupalGet('admin/structure/display-modes/form');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->drupalCreateUser(['administer display modes']));
    $this->drupalGet('admin/structure/display-modes/form');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Add form mode');
    $this->assertSession()->linkByHrefExists('admin/structure/display-modes/form/add');

    $this->drupalGet('admin/structure/display-modes/form/add/entity_test_no_label');
    $this->assertSession()->statusCodeEquals(404);

    $this->drupalGet('admin/structure/display-modes/form/add');
    $this->assertSession()->linkNotExists('Entity Test without label', 'An entity type with no form cannot have form modes.');

    // Test adding a view mode including dots in machine_name.
    $this->clickLink('Test entity');
    // Check if 'Name' field is required.
    $this->assertTrue($this->getSession()->getPage()->findField('label')->hasClass('required'));
    $edit = [
      'id' => $this->randomMachineName() . '.' . $this->randomMachineName(),
      'label' => $this->randomString(),
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name must contain only lowercase letters, numbers, and underscores.');

    // Test adding a form mode.
    $edit = [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("Saved the {$edit['label']} form mode.");

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
    $this->clickLink('Delete');
    $this->assertSession()->pageTextContains("Are you sure you want to delete the form mode {$edit['label']}?");
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains("The form mode {$edit['label']} has been deleted.");
  }

  /**
   * Tests if view modes appear in alphabetical order by visible name.
   *
   * The machine name should not be used for sorting.
   *
   * @see https://www.drupal.org/node/2858569
   */
  public function testAlphabeticalDisplaySettings() {
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer content types',
      'administer display modes',
      'administer nodes',
      'administer node fields',
      'administer node display',
      'administer node form display',
      'view the administration theme',
    ]));
    $this->drupalGet('admin/structure/types/manage/article/display');
    // Verify that the order of view modes is alphabetical by visible label.
    // Since the default view modes all have machine names which coincide with
    // the English labels, they should appear in alphabetical order, by default
    // if viewing the site in English and if no changes have been made. We will
    // verify this first.
    $page_text = $this->getTextContent();
    $start = strpos($page_text, 'view modes');
    $pos = $start;
    $list = ['Full content', 'RSS', 'Search index', 'Search result', 'Teaser'];
    // Verify that the order of the view modes is correct on the page.
    foreach ($list as $name) {
      $new_pos = strpos($page_text, $name, $start);
      $this->assertGreaterThan($pos, $new_pos);
      $pos = $new_pos;
    }
    // Now that we have verified the original display order, we can change the
    // label for one of the view modes. If we rename "Teaser" to "Breezier", it
    // should appear as the first of the listed view modes:
    // Set new values and enable test plugins.
    $edit = [
      'label' => 'Breezier',
    ];
    $this->drupalGet('admin/structure/display-modes/view/manage/node.teaser');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Saved the Breezier view mode.');

    // Re-open the display settings for the article content type and verify
    // that changing "Teaser" to "Breezier" makes it appear before "Full
    // content".
    $this->drupalGet('admin/structure/types/manage/article/display');
    $page_text = $this->getTextContent();
    $start = strpos($page_text, 'view modes');
    $pos = $start;
    $list = ['Breezier', 'Full content'];
    // Verify that the order of the view modes is correct on the page.
    foreach ($list as $name) {
      $new_pos = strpos($page_text, $name, $start);
      $this->assertGreaterThan($pos, $new_pos);
      $pos = $new_pos;
    }
  }

}
