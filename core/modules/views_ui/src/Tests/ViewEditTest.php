<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\ViewEditTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\views\Entity\View;
use Drupal\views\Views;

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
  public static $testViews = array('test_view', 'test_display', 'test_groupwise_term_ui');

  /**
   * Tests the delete link on a views UI.
   */
  public function testDeleteLink() {
    $this->drupalGet('admin/structure/views/view/test_view');
    $this->assertLink(t('Delete view'), 0, 'Ensure that the view delete link appears');

    $view = $this->container->get('entity.manager')->getStorage('view')->load('test_view');
    $this->assertTrue($view instanceof View);
    $this->clickLink(t('Delete view'));
    $this->assertUrl('admin/structure/views/view/test_view/delete');
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertRaw(t('The view %name has been deleted.', array('%name' => $view->label())));

    $this->assertUrl('admin/structure/views');
    $view = $this->container->get('entity.manager')->getStorage('view')->load('test_view');
    $this->assertFalse($view instanceof View);
  }

  /**
   * Tests the machine name form.
   */
  public function testMachineNameOption() {
    $this->drupalGet('admin/structure/views/view/test_view');
    // Add a new attachment display.
    $this->drupalPostForm(NULL, array(), 'Add Attachment');

    // Change the machine name for the display from page_1 to test_1.
    $edit = array('display_id' => 'test_1');
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/attachment_1/display_id', $edit, 'Apply');
    $this->assertLink(t('test_1'));

    // Save the view, and test the new ID has been saved.
    $this->drupalPostForm(NULL, array(), 'Save');
    $view = \Drupal::entityManager()->getStorage('view')->load('test_view');
    $displays = $view->get('display');
    $this->assertTrue(!empty($displays['test_1']), 'Display data found for new display ID key.');
    $this->assertIdentical($displays['test_1']['id'], 'test_1', 'New display ID matches the display ID key.');
    $this->assertFalse(array_key_exists('attachment_1', $displays), 'Old display ID not found.');

    // Test the form validation with invalid IDs.
    $machine_name_edit_url = 'admin/structure/views/nojs/display/test_view/test_1/display_id';
    $error_text = t('Display name must be letters, numbers, or underscores only.');

    // Test that potential invalid display ID requests are detected
    $this->drupalGet('admin/structure/views/ajax/handler/test_view/fake_display_name/filter/title');
    $this->assertText('Invalid display id fake_display_name');

    $edit = array('display_id' => 'test 1');
    $this->drupalPostForm($machine_name_edit_url, $edit, 'Apply');
    $this->assertText($error_text);

    $edit = array('display_id' => 'test_1#');
    $this->drupalPostForm($machine_name_edit_url, $edit, 'Apply');
    $this->assertText($error_text);

    // Test using an existing display ID.
    $edit = array('display_id' => 'default');
    $this->drupalPostForm($machine_name_edit_url, $edit, 'Apply');
    $this->assertText(t('Display id should be unique.'));

    // Test that the display ID has not been changed.
    $this->drupalGet('admin/structure/views/view/test_view/edit/test_1');
    $this->assertLink(t('test_1'));
  }

  /**
   * Tests the language options on the views edit form.
   */
  public function testEditFormLanguageOptions() {
    // Language options should not exist without language module.
    $test_views = array(
      'test_view' => 'default',
      'test_display' => 'page_1',
    );
    foreach ($test_views as $view_name => $display) {
      $this->drupalGet('admin/structure/views/view/' . $view_name);
      $this->assertResponse(200);
      $langcode_url = 'admin/structure/views/nojs/display/' . $view_name . '/' . $display . '/rendering_language';
      $this->assertNoLinkByHref($langcode_url);
      $this->assertNoLink(t('!type language selected for page', array('!type' => t('Content'))));
      $this->assertNoLink(t('Content language of view row'));
    }

    // Make the site multilingual and test the options again.
    $this->container->get('module_installer')->install(array('language', 'content_translation'));
    ConfigurableLanguage::createFromLangcode('hu')->save();
    $this->resetAll();
    $this->rebuildContainer();

    // Language options should now exist with entity language the default.
    foreach ($test_views as $view_name => $display) {
      $this->drupalGet('admin/structure/views/view/' . $view_name);
      $this->assertResponse(200);
      $langcode_url = 'admin/structure/views/nojs/display/' . $view_name . '/' . $display . '/rendering_language';
      if ($view_name == 'test_view') {
        $this->assertNoLinkByHref($langcode_url);
        $this->assertNoLink(t('!type language selected for page', array('!type' => t('Content'))));
        $this->assertNoLink(t('Content language of view row'));
      }
      else {
        $this->assertLinkByHref($langcode_url);
        $this->assertNoLink(t('!type language selected for page', array('!type' => t('Content'))));
        $this->assertLink(t('Content language of view row'));
      }

      $this->drupalGet($langcode_url);
      $this->assertResponse(200);
      if ($view_name == 'test_view') {
        $this->assertText(t('The view is not based on a translatable entity type or the site is not multilingual.'));
      }
      else {
        $this->assertFieldByName('rendering_language', '***LANGUAGE_entity_translation***');
        // Test that the order of the language list is similar to other language
        // lists, such as in the content translation settings.
        $expected_elements = array(
          '***LANGUAGE_entity_translation***',
          '***LANGUAGE_entity_default***',
          '***LANGUAGE_site_default***',
          '***LANGUAGE_language_interface***',
          'en',
          'hu',
        );
        $elements = $this->xpath('//select[@id="edit-rendering-language"]/option');
        // Compare values inside the option elements with expected values.
        for ($i = 0; $i < count($elements); $i++) {
          $this->assertEqual($elements[$i]->attributes()->{'value'}, $expected_elements[$i]);
        }

        // Check that the selected values are respected even we they are not
        // supposed to be listed.
        // Give permission to edit languages to authenticated users.
        $edit = [
          'authenticated[administer languages]' => TRUE,
        ];
        $this->drupalPostForm('/admin/people/permissions', $edit, t('Save permissions'));
        // Enable Content language negotiation so we have one more item
        // to select.
        $edit = [
          'language_content[configurable]' => TRUE,
        ];
        $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

        // Choose the new negotiation as the rendering language.
        $edit = [
          'rendering_language' => '***LANGUAGE_language_content***',
        ];
        $this->drupalPostForm('/admin/structure/views/nojs/display/' . $view_name . '/' . $display . '/rendering_language', $edit, t('Apply'));

        // Disable language content negotiation.
        $edit = [
          'language_content[configurable]' => FALSE,
        ];
        $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

        // Check that the previous selection is listed and selected.
        $this->drupalGet($langcode_url);
        $element = $this->xpath('//select[@id="edit-rendering-language"]/option[@value="***LANGUAGE_language_content***" and @selected="selected"]');
        $this->assertFalse(empty($element), 'Current selection is not lost');

        // Check the order for the langcode filter.
        $langcode_url = 'admin/structure/views/nojs/handler/' . $view_name . '/' . $display . '/filter/langcode';
        $this->drupalGet($langcode_url);
        $this->assertResponse(200);

        $expected_elements = array(
          'all',
          '***LANGUAGE_site_default***',
          '***LANGUAGE_language_interface***',
          '***LANGUAGE_language_content***',
          'en',
          'hu',
          'und',
          'zxx',
        );
        $elements = $this->xpath('//div[@id="edit-options-value"]//input');
        // Compare values inside the option elements with expected values.
        for ($i = 0; $i < count($elements); $i++) {
          $this->assertEqual($elements[$i]->attributes()->{'value'}, $expected_elements[$i]);
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
    $this->drupalPostForm('admin/structure/views/nojs/add-handler/test_groupwise_term_ui/default/relationship', $edit, 'Add and configure relationships');
    // Apply changes.
    $edit = array();
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_groupwise_term_ui/default/relationship/tid_representative', $edit, 'Apply');
  }

}
