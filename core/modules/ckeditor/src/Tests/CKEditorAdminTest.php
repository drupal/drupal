<?php

/**
 * @file
 * Contains \Drupal\ckeditor\Tests\CKEditorAdminTest.
 */

namespace Drupal\ckeditor\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\editor\Entity\Editor;
use Drupal\filter\FilterFormatInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests administration of CKEditor.
 *
 * @group ckeditor
 */
class CKEditorAdminTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter', 'editor', 'ckeditor');

  /**
   * A user with the 'administer filters' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
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
    $this->adminUser = $this->drupalCreateUser(array('administer filters'));
  }

  /**
   * Tests configuring a text editor for an existing text format.
   */
  function testExistingFormat() {
    $ckeditor = $this->container->get('plugin.manager.editor')->createInstance('ckeditor');

    $this->drupalLogin($this->adminUser);
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
              'name' => 'Formatting',
              'items' => array('Bold', 'Italic',),
            ),
            array(
              'name' => 'Links',
              'items' => array('DrupalLink', 'DrupalUnlink',),
            ),
            array(
              'name' => 'Lists',
              'items' => array('BulletedList', 'NumberedList',),
            ),
            array(
              'name' => 'Media',
              'items' => array('Blockquote', 'DrupalImage',),
            ),
            array(
              'name' => 'Tools',
              'items' => array('Source',),
            ),
          ),
        ),
      ),
      'plugins' => array(),
    );
    $this->assertIdentical($this->castSafeStrings($ckeditor->getDefaultSettings()), $expected_default_settings);

    // Keep the "CKEditor" editor selected and click the "Configure" button.
    $this->drupalPostAjaxForm(NULL, $edit, 'editor_configure');
    $editor = entity_load('editor', 'filtered_html');
    $this->assertFalse($editor, 'No Editor config entity exists yet.');

    // Ensure that drupalSettings is correct.
    $ckeditor_settings_toolbar = array(
      '#theme' => 'ckeditor_settings_toolbar',
      '#editor' => Editor::create(['editor' => 'ckeditor']),
      '#plugins' => $this->container->get('plugin.manager.ckeditor.plugin')->getButtons(),
    );
    $this->assertEqual(
      $this->drupalSettings['ckeditor']['toolbarAdmin'],
      $this->container->get('renderer')->renderPlain($ckeditor_settings_toolbar),
      'CKEditor toolbar settings are rendered as part of drupalSettings.'
    );

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
    $this->assertIdentical($expected_settings, $editor->getSettings(), 'The Editor config entity has the correct settings.');

    // Configure the Styles plugin, and ensure the updated settings are saved.
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $edit = array(
      'editor[settings][plugins][stylescombo][styles]' => "h1.title|Title\np.callout|Callout\n\n",
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $expected_settings['plugins']['stylescombo']['styles'] = "h1.title|Title\np.callout|Callout\n\n";
    $editor = entity_load('editor', 'filtered_html');
    $this->assertTrue($editor instanceof Editor, 'An Editor config entity exists.');
    $this->assertIdentical($expected_settings, $editor->getSettings(), 'The Editor config entity has the correct settings.');

    // Change the buttons that appear on the toolbar (in JavaScript, this is
    // done via drag and drop, but here we can only emulate the end result of
    // that interaction). Test multiple toolbar rows and a divider within a row.
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $expected_settings['toolbar']['rows'][0][] = array(
      'name' => 'Action history',
      'items' => array('Undo', '|', 'Redo', 'JustifyCenter'),
    );
    $edit = array(
      'editor[settings][toolbar][button_groups]' => json_encode($expected_settings['toolbar']['rows']),
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $editor = entity_load('editor', 'filtered_html');
    $this->assertTrue($editor instanceof Editor, 'An Editor config entity exists.');
    $this->assertIdentical($expected_settings, $editor->getSettings(), 'The Editor config entity has the correct settings.');

    // Check that the markup we're setting for the toolbar buttons (actually in
    // JavaScript's drupalSettings, and Unicode-escaped) is correctly rendered.
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    // Create function to encode HTML as we expect it in drupalSettings.
    $json_encode = function($html) {
      return trim(Json::encode($html), '"');
    };
    // Check the Button separator.
    $this->assertRaw($json_encode('<li data-drupal-ckeditor-button-name="-" class="ckeditor-button-separator ckeditor-multiple-button" data-drupal-ckeditor-type="separator"><a href="#" role="button" aria-label="Button separator" class="ckeditor-separator"></a></li>'));
    // Check the Format dropdown.
    $this->assertRaw($json_encode('<li data-drupal-ckeditor-button-name="Format" class="ckeditor-button"><a href="#" role="button" aria-label="Format"><span class="ckeditor-button-dropdown">Format<span class="ckeditor-button-arrow"></span></span></a></li>'));
    // Check the Styles dropdown.
    $this->assertRaw($json_encode('<li data-drupal-ckeditor-button-name="Styles" class="ckeditor-button"><a href="#" role="button" aria-label="Styles"><span class="ckeditor-button-dropdown">Styles<span class="ckeditor-button-arrow"></span></span></a></li>'));
    // Check strikethrough.
    $this->assertRaw($json_encode('<li data-drupal-ckeditor-button-name="Strike" class="ckeditor-button"><a href="#" class="cke-icon-only cke_ltr" role="button" title="strike" aria-label="strike"><span class="cke_button_icon cke_button__strike_icon">strike</span></a></li>'));

    // Now enable the ckeditor_test module, which provides one configurable
    // CKEditor plugin — this should not affect the Editor config entity.
    \Drupal::service('module_installer')->install(array('ckeditor_test'));
    $this->resetAll();
    $this->container->get('plugin.manager.ckeditor.plugin')->clearCachedDefinitions();
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $ultra_llama_mode_checkbox = $this->xpath('//input[@type="checkbox" and @name="editor[settings][plugins][llama_contextual_and_button][ultra_llama_mode]" and not(@checked)]');
    $this->assertTrue(count($ultra_llama_mode_checkbox) === 1, 'The "Ultra llama mode" checkbox exists and is not checked.');
    $editor = entity_load('editor', 'filtered_html');
    $this->assertTrue($editor instanceof Editor, 'An Editor config entity exists.');
    $this->assertIdentical($expected_settings, $editor->getSettings(), 'The Editor config entity has the correct settings.');

    // Finally, check the "Ultra llama mode" checkbox.
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $edit = array(
      'editor[settings][plugins][llama_contextual_and_button][ultra_llama_mode]' => '1',
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $ultra_llama_mode_checkbox = $this->xpath('//input[@type="checkbox" and @name="editor[settings][plugins][llama_contextual_and_button][ultra_llama_mode]" and @checked="checked"]');
    $this->assertTrue(count($ultra_llama_mode_checkbox) === 1, 'The "Ultra llama mode" checkbox exists and is checked.');
    $expected_settings['plugins']['llama_contextual_and_button']['ultra_llama_mode'] = TRUE;
    $editor = entity_load('editor', 'filtered_html');
    $this->assertTrue($editor instanceof Editor, 'An Editor config entity exists.');
    $this->assertIdentical($expected_settings, $editor->getSettings());
  }

  /**
   * Tests configuring a text editor for a new text format.
   *
   * This test only needs to ensure that the basics of the CKEditor
   * configuration form work; details are tested in testExistingFormat().
   */
  function testNewFormat() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/formats/add');

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

    // Name our fancy new text format, select the "CKEditor" editor and click
    // the "Configure" button.
    $edit = array(
      'name' => 'My amazing text format',
      'format' => 'amazing_format',
      'editor[editor]' => 'ckeditor',
    );
    $this->drupalPostAjaxForm(NULL, $edit, 'editor_configure');
    $filter_format = entity_load('filter_format', 'amazing_format');
    $this->assertFalse($filter_format, 'No FilterFormat config entity exists yet.');
    $editor = entity_load('editor', 'amazing_format');
    $this->assertFalse($editor, 'No Editor config entity exists yet.');

    // Ensure the toolbar buttons configuration value is initialized to the
    // default value.
    $ckeditor = $this->container->get('plugin.manager.editor')->createInstance('ckeditor');
    $default_settings = $ckeditor->getDefaultSettings();
    $expected_buttons_value = json_encode($default_settings['toolbar']['rows']);
    $this->assertFieldByName('editor[settings][toolbar][button_groups]', $expected_buttons_value);

    // Ensure the styles textarea exists and is initialized empty.
    $styles_textarea = $this->xpath('//textarea[@name="editor[settings][plugins][stylescombo][styles]"]');
    $this->assertFieldByXPath('//textarea[@name="editor[settings][plugins][stylescombo][styles]"]', '', 'The styles textarea exists and is empty.');
    $this->assertTrue(count($styles_textarea) === 1, 'The "styles" textarea exists.');

    // Submit the form to create both a new text format and an associated text
    // editor.
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    // Ensure a FilterFormat object exists now.
    $filter_format = entity_load('filter_format', 'amazing_format');
    $this->assertTrue($filter_format instanceof FilterFormatInterface, 'A FilterFormat config entity exists now.');

    // Ensure an Editor object exists now, with the proper settings.
    $expected_settings = $default_settings;
    $expected_settings['plugins']['stylescombo']['styles'] = '';
    $editor = entity_load('editor', 'amazing_format');
    $this->assertTrue($editor instanceof Editor, 'An Editor config entity exists now.');
    $this->assertIdentical($this->castSafeStrings($expected_settings), $this->castSafeStrings($editor->getSettings()), 'The Editor config entity has the correct settings.');
  }

}
