<?php

/**
 * @file
 * Definition of \Drupal\editor\Tests\EditorAdminTest.
 */

namespace Drupal\editor\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests administration of text editors.
 */
class EditorAdminTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter', 'editor');

  public static function getInfo() {
    return array(
      'name' => 'Text editor administration',
      'description' => 'Tests administration of text editors.',
      'group' => 'Text Editor',
    );
  }

  function setUp() {
    parent::setUp();

   // Add text format.
    $filtered_html_format = array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => array(),
    );
    $filtered_html_format = (object) $filtered_html_format;
    filter_format_save($filtered_html_format);

    // Create admin user.
    $this->admin_user = $this->drupalCreateUser(array('administer filters'));
  }

  function testWithoutEditorAvailable() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/content/formats/filtered_html');

    // Ensure the form field order is correct.
    $roles_pos = strpos($this->drupalGetContent(), 'Roles');
    $editor_pos = strpos($this->drupalGetContent(), 'Text editor');
    $filters_pos = strpos($this->drupalGetContent(), 'Enabled filters');
    $this->assertTrue($roles_pos < $editor_pos && $editor_pos < $filters_pos, '"Text Editor" select appears in the correct location of the text format configuration UI.');

    // Verify the <select>.
    $select = $this->xpath('//select[@name="editor"]');
    $select_is_disabled = $this->xpath('//select[@name="editor" and @disabled="disabled"]');
    $options = $this->xpath('//select[@name="editor"]/option');
    $this->assertTrue(count($select) === 1, 'The Text Editor select exists.');
    $this->assertTrue(count($select_is_disabled) === 1, 'The Text Editor select is disabled.');
    $this->assertTrue(count($options) === 1, 'The Text Editor select has only one option.');
    $this->assertTrue(((string) $options[0]) === 'None', 'Option 1 in the he Text Editor select is "None".');
    $this->assertRaw(t('This option is disabled because no modules that provide a text editor are currently enabled.'), 'Description for select present that tells users to install a text editor module.');

    // Make a text editor available.
    module_enable(array('editor_test'));
    $this->rebuildContainer();
    $this->resetAll();
    $this->drupalGet('admin/config/content/formats/filtered_html');

    // Verify the <select> when a text editor is available.
    $select = $this->xpath('//select[@name="editor"]');
    $select_is_disabled = $this->xpath('//select[@name="editor" and @disabled="disabled"]');
    $options = $this->xpath('//select[@name="editor"]/option');
    $this->assertTrue(count($select) === 1, 'The Text Editor select exists.');
    $this->assertTrue(count($select_is_disabled) === 0, 'The Text Editor select is not disabled.');
    $this->assertTrue(count($options) === 2, 'The Text Editor select has two options.');
    $this->assertTrue(((string) $options[0]) === 'None', 'Option 1 in the he Text Editor select is "None".');
    $this->assertTrue(((string) $options[1]) === 'Unicorn Editor', 'Option 2 in the he Text Editor select is "Unicorn Editor".');
    $this->assertTrue(((string) $options[0]['selected']) === 'selected', 'Option 1 ("None") is selected.');
    // Ensure the none option is selected
    $this->assertNoRaw(t('This option is disabled because no modules that provide a text editor are currently enabled.'), 'Description for select absent that tells users to install a text editor module.');

    // Select the "Unicorn Editor" editor and click the "Configure" button.
    $edit = array(
      'editor' => 'unicorn',
    );
    $this->drupalPostAjax(NULL, $edit, 'editor_configure');
    $unicorn_setting_foo = $this->xpath('//input[@name="editor_settings[foo]" and @type="text" and @value="bar"]');
    $this->assertTrue(count($unicorn_setting_foo), "Unicorn Editor's settings form is present.");
    $options = $this->xpath('//select[@name="editor"]/option');

    // Now configure the setting to another value.
    $edit['editor_settings[foo]'] = 'baz';
    $this->drupalPost(NULL, $edit, t('Save configuration'));

    // Verify the editor configuration is saved correctly.
    $editor = editor_load('filtered_html');
    $this->assertIdentical($editor->editor, 'unicorn', 'The text editor is configured correctly.');
    $this->assertIdentical($editor->settings['foo'], 'baz', 'The text editor settings are stored correctly.');
    $this->assertIdentical($editor->settings['ponies too'], true, 'The text editor defaults are retrieved correctly.');
    $this->assertIdentical($editor->settings['rainbows'], true, 'The text editor defaults added by hook_editor_settings_defaults() are retrieved correctly.');
    $this->assertIdentical($editor->settings['sparkles'], false, 'The text editor defaults modified by hook_editor_settings_defaults_alter() are retrieved correctly.');
    $this->drupalGet('admin/config/content/formats/filtered_html');
    $select = $this->xpath('//select[@name="editor"]');
    $select_is_disabled = $this->xpath('//select[@name="editor" and @disabled="disabled"]');
    $options = $this->xpath('//select[@name="editor"]/option');
    $this->assertTrue(count($select) === 1, 'The Text Editor select exists.');
    $this->assertTrue(count($select_is_disabled) === 0, 'The Text Editor select is not disabled.');
    $this->assertTrue(count($options) === 2, 'The Text Editor select has two options.');
    $this->assertTrue(((string) $options[1]['selected']) === 'selected', 'Option 2 ("Unicorn Editor") is selected.');
  }
}
