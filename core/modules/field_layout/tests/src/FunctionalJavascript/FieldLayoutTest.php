<?php

namespace Drupal\Tests\field_layout\FunctionalJavascript;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests using field layout for entity displays.
 *
 * @group field_layout
 */
class FieldLayoutTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field_layout', 'field_ui', 'field_layout_test', 'layout_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $entity = EntityTest::create([
      'name' => 'The name for this entity',
      'field_test_text' => [[
        'value' => 'The field test text value',
      ]],
    ]);
    $entity->save();
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'view test entity',
      'administer entity_test content',
      'administer entity_test fields',
      'administer entity_test display',
      'administer entity_test form display',
      'view the administration theme',
    ]));
  }

  /**
   * Tests that layouts are unique per view mode.
   */
  public function testEntityViewModes() {
    // By default, the field is not visible.
    $this->drupalGet('entity_test/1/test');
    $this->assertSession()->elementNotExists('css', '.layout-region--content .field--name-field-test-text');
    $this->drupalGet('entity_test/1');
    $this->assertSession()->elementNotExists('css', '.layout-region--content .field--name-field-test-text');

    // Change the layout for the "test" view mode. See
    // core.entity_view_mode.entity_test.test.yml.
    $this->drupalGet('entity_test/structure/entity_test/display');
    $this->click('#edit-modes');
    $this->getSession()->getPage()->checkField('display_modes_custom[test]');
    $this->submitForm([], 'Save');
    $this->clickLink('configure them');
    $this->getSession()->getPage()->pressButton('Show row weights');
    $this->getSession()->getPage()->selectFieldOption('fields[field_test_text][region]', 'content');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');

    // Each view mode has a different layout.
    $this->drupalGet('entity_test/1/test');
    $this->assertSession()->elementExists('css', '.layout-region--content .field--name-field-test-text');
    $this->drupalGet('entity_test/1');
    $this->assertSession()->elementNotExists('css', '.layout-region--content .field--name-field-test-text');
  }

  /**
   * Tests the use of field layout for entity form displays.
   */
  public function testEntityForm() {
    // By default, the one-column layout is used.
    $this->drupalGet('entity_test/manage/1/edit');
    $this->assertFieldInRegion('field_test_text[0][value]', 'content');

    // The one-column layout is in use.
    $this->drupalGet('entity_test/structure/entity_test/form-display');
    $this->assertEquals(['Content', 'Disabled'], $this->getRegionTitles());

    // Switch the layout to two columns.
    $this->click('#edit-field-layouts');
    $this->getSession()->getPage()->selectFieldOption('field_layout', 'layout_twocol');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');

    // The field is moved to the default region for the new layout.
    $this->assertSession()->pageTextContains('Your settings have been saved.');
    $this->assertEquals(['Top', 'Left', 'Right', 'Bottom', 'Disabled'], $this->getRegionTitles());

    $this->drupalGet('entity_test/manage/1/edit');
    // No fields are visible, and the regions don't display when empty.
    $this->assertFieldInRegion('field_test_text[0][value]', 'left');
    $this->assertSession()->elementExists('css', '.layout-region--left .field--name-field-test-text');

    // After a refresh the new regions are still there.
    $this->drupalGet('entity_test/structure/entity_test/form-display');
    $this->assertEquals(['Top', 'Left', 'Right', 'Bottom', 'Disabled'], $this->getRegionTitles());

    // Drag the field to the right region.
    $field_test_text_row = $this->getSession()->getPage()->find('css', '#field-test-text');
    $right_region_row = $this->getSession()->getPage()->find('css', '.region-right-message');
    $field_test_text_row->find('css', '.handle')->dragTo($right_region_row);
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');

    // The new layout is used.
    $this->drupalGet('entity_test/manage/1/edit');
    $this->assertSession()->elementExists('css', '.layout-region--right .field--name-field-test-text');
    $this->assertFieldInRegion('field_test_text[0][value]', 'right');

    // Move the field to the right region without tabledrag.
    $this->drupalGet('entity_test/structure/entity_test/form-display');
    $this->getSession()->getPage()->pressButton('Show row weights');
    $this->getSession()->getPage()->selectFieldOption('fields[field_test_text][region]', 'right');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');

    // The updated region is used.
    $this->drupalGet('entity_test/manage/1/edit');
    $this->assertFieldInRegion('field_test_text[0][value]', 'right');

    // The layout is still in use without Field UI.
    $this->container->get('module_installer')->uninstall(['field_ui']);
    $this->drupalGet('entity_test/manage/1/edit');
    $this->assertFieldInRegion('field_test_text[0][value]', 'right');
  }

  /**
   * Tests the use of field layout for entity view displays.
   */
  public function testEntityView() {
    // The one-column layout is in use.
    $this->drupalGet('entity_test/structure/entity_test/display');
    $this->assertEquals(['Content', 'Disabled'], $this->getRegionTitles());

    // Switch the layout to two columns.
    $this->click('#edit-field-layouts');
    $this->getSession()->getPage()->selectFieldOption('field_layout', 'layout_twocol');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');

    $this->assertSession()->pageTextContains('Your settings have been saved.');
    $this->assertEquals(['Top', 'Left', 'Right', 'Bottom', 'Disabled'], $this->getRegionTitles());

    $this->drupalGet('entity_test/1');
    // No fields are visible, and the regions don't display when empty.
    $this->assertSession()->elementNotExists('css', '.layout--twocol');
    $this->assertSession()->elementNotExists('css', '.layout-region');
    $this->assertSession()->elementNotExists('css', '.field--name-field-test-text');

    // After a refresh the new regions are still there.
    $this->drupalGet('entity_test/structure/entity_test/display');
    $this->assertEquals(['Top', 'Left', 'Right', 'Bottom', 'Disabled'], $this->getRegionTitles());

    // Drag the field to the left region.
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'hidden')->isSelected());
    $field_test_text_row = $this->getSession()->getPage()->find('css', '#field-test-text');
    $left_region_row = $this->getSession()->getPage()->find('css', '.region-left-message');
    $field_test_text_row->find('css', '.handle')->dragTo($left_region_row);
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertFalse($this->assertSession()->optionExists('fields[field_test_text][region]', 'hidden')->isSelected());
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');

    // The new layout is used.
    $this->drupalGet('entity_test/1');
    $this->assertSession()->elementExists('css', '.layout--twocol');
    $this->assertSession()->elementExists('css', '.layout-region--left .field--name-field-test-text');

    // Move the field to the right region without tabledrag.
    $this->drupalGet('entity_test/structure/entity_test/display');
    $this->getSession()->getPage()->pressButton('Show row weights');
    $this->getSession()->getPage()->selectFieldOption('fields[field_test_text][region]', 'right');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');

    // The updated region is used.
    $this->drupalGet('entity_test/1');
    $this->assertSession()->elementExists('css', '.layout-region--right .field--name-field-test-text');

    // The layout is still in use without Field UI.
    $this->container->get('module_installer')->uninstall(['field_ui']);
    $this->drupalGet('entity_test/1');
    $this->assertSession()->elementExists('css', '.layout--twocol');
    $this->assertSession()->elementExists('css', '.layout-region--right .field--name-field-test-text');
  }

  /**
   * Tests layout plugins with forms.
   */
  public function testLayoutForms() {
    $this->drupalGet('entity_test/structure/entity_test/display');
    // Switch to a field layout with settings.
    $this->click('#edit-field-layouts');

    // Test switching between layouts with and without forms.
    $this->getSession()->getPage()->selectFieldOption('field_layout', 'layout_test_plugin');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldExists('settings_wrapper[layout_settings][setting_1]');

    $this->getSession()->getPage()->selectFieldOption('field_layout', 'layout_test_2col');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldNotExists('settings_wrapper[layout_settings][setting_1]');

    $this->getSession()->getPage()->selectFieldOption('field_layout', 'layout_test_plugin');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldExists('settings_wrapper[layout_settings][setting_1]');

    // Move the test field to the content region.
    $this->getSession()->getPage()->pressButton('Show row weights');
    $this->getSession()->getPage()->selectFieldOption('fields[field_test_text][region]', 'content');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');

    $this->drupalGet('entity_test/1');
    $this->assertSession()->pageTextContains('Blah: Default');

    // Update the field layout settings.
    $this->drupalGet('entity_test/structure/entity_test/display');
    $this->click('#edit-field-layouts');
    $this->getSession()->getPage()->fillField('settings_wrapper[layout_settings][setting_1]', 'Test text');
    $this->submitForm([], 'Save');

    $this->drupalGet('entity_test/1');
    $this->assertSession()->pageTextContains('Blah: Test text');
  }

  /**
   * Gets the region titles on the page.
   *
   * @return string[]
   *   An array of region titles.
   */
  protected function getRegionTitles() {
    $region_titles = [];
    $region_title_elements = $this->getSession()->getPage()->findAll('css', '.region-title td');
    /** @var \Behat\Mink\Element\NodeElement[] $region_title_elements */
    foreach ($region_title_elements as $region_title_element) {
      $region_titles[] = $region_title_element->getText();
    }
    return $region_titles;
  }

  /**
   * Asserts that a field exists in a given region.
   *
   * @param string $field_selector
   *   The field selector, one of field id|name|label|value.
   * @param string $region_name
   *   The machine name of the region.
   */
  protected function assertFieldInRegion($field_selector, $region_name) {
    $region_element = $this->getSession()->getPage()->find('css', ".layout-region--$region_name");
    $this->assertNotNull($region_element);
    $this->assertSession()->fieldExists($field_selector, $region_element);
  }

}
