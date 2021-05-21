<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\views\Entity\View;

/**
 * Tests some general functionality of editing views, like deleting a view.
 *
 * @group views_ui
 */
class ViewEditTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view', 'test_display', 'test_groupwise_term_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the delete link on a views UI.
   */
  public function testDeleteLink() {
    $this->drupalGet('admin/structure/views/view/test_view');
    $this->assertSession()->linkExists('Delete view', 0, 'Ensure that the view delete link appears');

    $view = $this->container->get('entity_type.manager')->getStorage('view')->load('test_view');
    $this->assertInstanceOf(View::class, $view);
    $this->clickLink(t('Delete view'));
    $this->assertSession()->addressEquals('admin/structure/views/view/test_view/delete');
    $this->submitForm([], 'Delete');
    $this->assertRaw(t('The view %name has been deleted.', ['%name' => $view->label()]));

    $this->assertSession()->addressEquals('admin/structure/views');
    $view = $this->container->get('entity_type.manager')->getStorage('view')->load('test_view');
    $this->assertNotInstanceOf(View::class, $view);
  }

  /**
   * Tests the machine name and administrative comment forms.
   */
  public function testOtherOptions() {
    $this->drupalGet('admin/structure/views/view/test_view');
    // Add a new attachment display.
    $this->submitForm([], 'Add Attachment');

    // Test that a long administrative comment is truncated.
    $edit = ['display_comment' => 'one two three four five six seven eight nine ten eleven twelve thirteen fourteen fifteen'];
    $this->drupalGet('admin/structure/views/nojs/display/test_view/attachment_1/display_comment');
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains('one two three four five six seven eight nine ten eleven twelve thirteen fourteen...');

    // Change the machine name for the display from page_1 to test_1.
    $edit = ['display_id' => 'test_1'];
    $this->drupalGet('admin/structure/views/nojs/display/test_view/attachment_1/display_id');
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->linkExists('test_1');

    // Save the view, and test the new ID has been saved.
    $this->submitForm([], 'Save');
    $view = \Drupal::entityTypeManager()->getStorage('view')->load('test_view');
    $displays = $view->get('display');
    $this->assertTrue(!empty($displays['test_1']), 'Display data found for new display ID key.');
    $this->assertSame('test_1', $displays['test_1']['id'], 'New display ID matches the display ID key.');
    $this->assertArrayNotHasKey('attachment_1', $displays);

    // Set to the same machine name and save the View.
    $edit = ['display_id' => 'test_1'];
    $this->drupalGet('admin/structure/views/nojs/display/test_view/test_1/display_id');
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');
    $this->assertSession()->linkExists('test_1');

    // Test the form validation with invalid IDs.
    $machine_name_edit_url = 'admin/structure/views/nojs/display/test_view/test_1/display_id';
    $error_text = 'Display machine name must contain only lowercase letters, numbers, or underscores.';

    // Test that potential invalid display ID requests are detected
    try {
      $this->drupalGet('admin/structure/views/ajax/handler/test_view/fake_display_name/filter/title');
      $this->fail('Expected error, when setDisplay() called with invalid display ID');
    }
    catch (\Exception $e) {
      $this->assertStringContainsString('setDisplay() called with invalid display ID "fake_display_name".', $e->getMessage());
    }

    $edit = ['display_id' => 'test 1'];
    $this->drupalGet($machine_name_edit_url);
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains($error_text);

    $edit = ['display_id' => 'test_1#'];
    $this->drupalGet($machine_name_edit_url);
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains($error_text);

    // Test using an existing display ID.
    $edit = ['display_id' => 'default'];
    $this->drupalGet($machine_name_edit_url);
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains('Display id should be unique.');

    // Test that the display ID has not been changed.
    $this->drupalGet('admin/structure/views/view/test_view/edit/test_1');
    $this->assertSession()->linkExists('test_1');

    // Test that validation does not run on cancel.
    $this->drupalGet('admin/structure/views/view/test_view');
    // Delete the field to cause an error on save.
    $fields = [];
    $fields['fields[age][removed]'] = 1;
    $fields['fields[id][removed]'] = 1;
    $fields['fields[name][removed]'] = 1;
    $this->drupalGet('admin/structure/views/nojs/rearrange/test_view/default/field');
    $this->submitForm($fields, 'Apply');
    $this->submitForm([], 'Save');
    $this->submitForm([], 'Cancel');
    // Verify that no error message is displayed.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "error")]');
    // Verify page was redirected to the view listing.
    $this->assertSession()->addressEquals('admin/structure/views');
  }

  /**
   * Tests the language options on the views edit form.
   */
  public function testEditFormLanguageOptions() {
    $assert_session = $this->assertSession();

    // Language options should not exist without language module.
    $test_views = [
      'test_view' => 'default',
      'test_display' => 'page_1',
    ];
    foreach ($test_views as $view_name => $display) {
      $this->drupalGet('admin/structure/views/view/' . $view_name);
      $this->assertSession()->statusCodeEquals(200);
      $langcode_url = 'admin/structure/views/nojs/display/' . $view_name . '/' . $display . '/rendering_language';
      $this->assertSession()->linkByHrefNotExists($langcode_url);
      $assert_session->linkNotExistsExact('Content language selected for page');
      $this->assertSession()->linkNotExists('Content language of view row');
    }

    // Make the site multilingual and test the options again.
    $this->container->get('module_installer')->install(['language', 'content_translation']);
    ConfigurableLanguage::createFromLangcode('hu')->save();
    $this->resetAll();
    $this->rebuildContainer();

    // Language options should now exist with entity language the default.
    foreach ($test_views as $view_name => $display) {
      $this->drupalGet('admin/structure/views/view/' . $view_name);
      $this->assertSession()->statusCodeEquals(200);
      $langcode_url = 'admin/structure/views/nojs/display/' . $view_name . '/' . $display . '/rendering_language';
      if ($view_name == 'test_view') {
        $this->assertSession()->linkByHrefNotExists($langcode_url);
        $assert_session->linkNotExistsExact('Content language selected for page');
        $this->assertSession()->linkNotExists('Content language of view row');
      }
      else {
        $this->assertSession()->linkByHrefExists($langcode_url);
        $assert_session->linkNotExistsExact('Content language selected for page');
        $this->assertSession()->linkExists('Content language of view row');
      }

      $this->drupalGet($langcode_url);
      $this->assertSession()->statusCodeEquals(200);
      if ($view_name == 'test_view') {
        $this->assertSession()->pageTextContains('The view is not based on a translatable entity type or the site is not multilingual.');
      }
      else {
        $this->assertSession()->fieldValueEquals('rendering_language', '***LANGUAGE_entity_translation***');
        // Test that the order of the language list is similar to other language
        // lists, such as in the content translation settings.
        $expected_elements = [
          '***LANGUAGE_entity_translation***',
          '***LANGUAGE_entity_default***',
          '***LANGUAGE_site_default***',
          '***LANGUAGE_language_interface***',
          'en',
          'hu',
        ];
        $elements = $this->assertSession()->selectExists('edit-rendering-language')->findAll('css', 'option');
        $elements = array_map(function ($element) {
          return $element->getValue();
        }, $elements);
        $this->assertSame($expected_elements, $elements);

        // Check that the selected values are respected even we they are not
        // supposed to be listed.
        // Give permission to edit languages to authenticated users.
        $edit = [
          'authenticated[administer languages]' => TRUE,
        ];
        $this->drupalGet('/admin/people/permissions');
        $this->submitForm($edit, 'Save permissions');
        // Enable Content language negotiation so we have one more item
        // to select.
        $edit = [
          'language_content[configurable]' => TRUE,
        ];
        $this->drupalGet('admin/config/regional/language/detection');
        $this->submitForm($edit, 'Save settings');

        // Choose the new negotiation as the rendering language.
        $edit = [
          'rendering_language' => '***LANGUAGE_language_content***',
        ];
        $this->drupalGet('/admin/structure/views/nojs/display/' . $view_name . '/' . $display . '/rendering_language');
        $this->submitForm($edit, 'Apply');

        // Disable language content negotiation.
        $edit = [
          'language_content[configurable]' => FALSE,
        ];
        $this->drupalGet('admin/config/regional/language/detection');
        $this->submitForm($edit, 'Save settings');

        // Check that the previous selection is listed and selected.
        $this->drupalGet($langcode_url);
        $this->assertTrue($this->assertSession()->optionExists('edit-rendering-language', '***LANGUAGE_language_content***')->isSelected());

        // Check the order for the langcode filter.
        $langcode_url = 'admin/structure/views/nojs/handler/' . $view_name . '/' . $display . '/filter/langcode';
        $this->drupalGet($langcode_url);
        $this->assertSession()->statusCodeEquals(200);

        $expected_elements = [
          'all',
          '***LANGUAGE_site_default***',
          '***LANGUAGE_language_interface***',
          '***LANGUAGE_language_content***',
          'en',
          'hu',
          'und',
          'zxx',
        ];
        $elements = $this->xpath('//div[@id="edit-options-value"]//input');
        // Compare values inside the option elements with expected values.
        for ($i = 0; $i < count($elements); $i++) {
          $this->assertEquals($expected_elements[$i], $elements[$i]->getAttribute('value'));
        }
      }
    }
  }

  /**
   * Tests Representative Node for a Taxonomy Term.
   */
  public function testRelationRepresentativeNode() {
    // Populate and submit the form.
    $edit["name[taxonomy_term_field_data.tid_representative]"] = TRUE;
    $this->drupalGet('admin/structure/views/nojs/add-handler/test_groupwise_term_ui/default/relationship');
    $this->submitForm($edit, 'Add and configure relationships');
    // Apply changes.
    $edit = [];
    $this->drupalGet('admin/structure/views/nojs/handler/test_groupwise_term_ui/default/relationship/tid_representative');
    $this->submitForm($edit, 'Apply');
  }

}
