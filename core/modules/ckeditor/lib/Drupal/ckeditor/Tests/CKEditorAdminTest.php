<?php

/**
 * @file
 * Definition of \Drupal\ckeditor\Tests\CKEditorAdminTest.
 */

namespace Drupal\ckeditor\Tests;

use Drupal\editor\Entity\Editor;
use Drupal\simpletest\WebTestBase;

/**
 * Tests administration of CKEditor.
 */
class CKEditorAdminTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter', 'editor', 'ckeditor');

  public static function getInfo() {
    return array(
      'name' => 'CKEditor administration',
      'description' => 'Tests administration of CKEditor.',
      'group' => 'CKEditor',
    );
  }

  function setUp() {
    parent::setUp();

    // Create text format.
    $filtered_html_format = entity_create('filter_format', array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => array(),
    ));
    $filtered_html_format->save();

    // Create admin user.
    $this->admin_user = $this->drupalCreateUser(array('administer filters'));
  }

  function testAdmin() {
    $ckeditor = $this->container->get('plugin.manager.editor')->createInstance('ckeditor');

    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');

    // Ensure no Editor config entity exists yet.
    $editor = entity_load('editor', 'filtered_html');
    $this->assertFalse($editor, 'No Editor config entity exists yet.');

    // Verify the "Text Editor" <select> when a text editor is available.
    $select = $this->xpath('//select[@name="editor[editor]"]');
    $select_is_disabled = $this->xpath('//select[@name="editor[editor]" and @disabled="disabled"]');
    $options = $this->xpath('//select[@name="editor[editor]"]/option');
    $this->assertTrue(count($select) === 1, 'The Text Editor select exists.');
    $this->assertTrue(count($select_is_disabled) === 0, 'The Text Editor select is not disabled.');
    $this->assertTrue(count($options) === 2, 'The Text Editor select has two options.');
    $this->assertTrue(((string) $options[0]) === 'None', 'Option 1 in the Text Editor select is "None".');
    $this->assertTrue(((string) $options[1]) === 'CKEditor', 'Option 2 in the Text Editor select is "CKEditor".');
    $this->assertTrue(((string) $options[0]['selected']) === 'selected', 'Option 1 ("None") is selected.');

    // Select the "CKEditor" editor and click the "Save configuration" button.
    $edit = array(
      'editor[editor]' => 'ckeditor',
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertRaw(t('You must configure the selected text editor.'));

    // Ensure the CKEditor editor returns the expected default settings.
    $expected_default_settings = array(
      'toolbar' => array(
        'rows' => array(
          // Button groups
          array(
            array(
              'name' => t('Formatting'),
              'items' => array('Bold', 'Italic',),
            ),
            array(
              'name' => t('Links'),
              'items' => array('DrupalLink', 'DrupalUnlink',),
            ),
            array(
              'name' => t('Lists'),
              'items' => array('BulletedList', 'NumberedList',),
            ),
            array(
              'name' => t('Media'),
              'items' => array('Blockquote', 'DrupalImage',),
            ),
            array(
              'name' => t('Tools'),
              'items' => array('Source',),
            ),
          ),
        ),
      ),
      'plugins' => array(),
    );
    $this->assertIdentical($ckeditor->getDefaultSettings(), $expected_default_settings);

    // Keep the "CKEditor" editor selected and click the "Configure" button.
    $this->drupalPostAjaxForm(NULL, $edit, 'editor_configure');
    $editor = entity_load('editor', 'filtered_html');
    $this->assertFalse($editor, 'No Editor config entity exists yet.');

    // Ensure the toolbar buttons configuration value is initialized to the
    // expected default value.
    $expected_buttons_value = json_encode($expected_default_settings['toolbar']['rows']);
    $this->assertFieldByName('editor[settings][toolbar][button_groups]', $expected_buttons_value);

    // Ensure the styles textarea exists and is initialized empty.
    $styles_textarea = $this->xpath('//textarea[@name="editor[settings][plugins][stylescombo][styles]"]');
    $this->assertFieldByXPath('//textarea[@name="editor[settings][plugins][stylescombo][styles]"]', '', 'The styles textarea exists and is empty.');
    $this->assertTrue(count($styles_textarea) === 1, 'The "styles" textarea exists.');

    // Submit the form to save the selection of CKEditor as the chosen editor.
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    // Ensure an Editor object exists now, with the proper settings.
    $expected_settings = $expected_default_settings;
    $expected_settings['plugins']['stylescombo']['styles'] = '';
    $editor = entity_load('editor', 'filtered_html');
    $this->assertTrue($editor instanceof Editor, 'An Editor config entity exists now.');
    $this->assertIdentical($expected_settings, $editor->settings, 'The Editor config entity has the correct settings.');

    // Configure the Styles plugin, and ensure the updated settings are saved.
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $edit = array(
      'editor[settings][plugins][stylescombo][styles]' => "h1.title|Title\np.callout|Callout\n\n",
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $expected_settings['plugins']['stylescombo']['styles'] = "h1.title|Title\np.callout|Callout\n\n";
    $editor = entity_load('editor', 'filtered_html');
    $this->assertTrue($editor instanceof Editor, 'An Editor config entity exists.');
    $this->assertIdentical($expected_settings, $editor->settings, 'The Editor config entity has the correct settings.');

    // Change the buttons that appear on the toolbar (in JavaScript, this is
    // done via drag and drop, but here we can only emulate the end result of
    // that interaction). Test multiple toolbar rows and a divider within a row.
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $expected_settings['toolbar']['rows'][0][] = array(
      'name' => 'Action history',
      'items' => array('Undo', '|', 'Redo'),
      array('JustifyCenter')
    );
    $edit = array(
      'editor[settings][toolbar][button_groups]' => json_encode($expected_settings['toolbar']['rows']),
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $editor = entity_load('editor', 'filtered_html');
    $this->assertTrue($editor instanceof Editor, 'An Editor config entity exists.');
    $this->assertIdentical($expected_settings, $editor->settings, 'The Editor config entity has the correct settings.');

    // Now enable the ckeditor_test module, which provides one configurable
    // CKEditor plugin — this should not affect the Editor config entity.
    \Drupal::moduleHandler()->install(array('ckeditor_test'));
    $this->rebuildContainer();
    $this->container->get('plugin.manager.ckeditor.plugin')->clearCachedDefinitions();
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $ultra_llama_mode_checkbox = $this->xpath('//input[@type="checkbox" and @name="editor[settings][plugins][llama_contextual_and_button][ultra_llama_mode]" and not(@checked)]');
    $this->assertTrue(count($ultra_llama_mode_checkbox) === 1, 'The "Ultra llama mode" checkbox exists and is not checked.');
    $editor = entity_load('editor', 'filtered_html');
    $this->assertTrue($editor instanceof Editor, 'An Editor config entity exists.');
    $this->assertIdentical($expected_settings, $editor->settings, 'The Editor config entity has the correct settings.');

    // Finally, check the "Ultra llama mode" checkbox.
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $edit = array(
      'editor[settings][plugins][llama_contextual_and_button][ultra_llama_mode]' => '1',
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $ultra_llama_mode_checkbox = $this->xpath('//input[@type="checkbox" and @name="editor[settings][plugins][llama_contextual_and_button][ultra_llama_mode]" and @checked="checked"]');
    $this->assertTrue(count($ultra_llama_mode_checkbox) === 1, 'The "Ultra llama mode" checkbox exists and is checked.');
    $expected_settings['plugins']['llama_contextual_and_button']['ultra_llama_mode'] = 1;
    $editor = entity_load('editor', 'filtered_html');
    $this->assertTrue($editor instanceof Editor, 'An Editor config entity exists.');
    $this->assertIdentical($expected_settings, $editor->settings);
  }

}
