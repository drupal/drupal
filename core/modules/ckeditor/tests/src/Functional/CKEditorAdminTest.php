<?php

namespace Drupal\Tests\ckeditor\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\editor\Entity\Editor;
use Drupal\filter\FilterFormatInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests administration of CKEditor.
 *
 * @group ckeditor
 */
class CKEditorAdminTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['filter', 'editor', 'ckeditor'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with the 'administer filters' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp(): void {
    parent::setUp();

    // Create text format.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => [],
    ]);
    $filtered_html_format->save();

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser(['administer filters']);
  }

  /**
   * Tests configuring a text editor for an existing text format.
   */
  public function testExistingFormat() {
    $ckeditor = $this->container->get('plugin.manager.editor')->createInstance('ckeditor');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');

    // Ensure no Editor config entity exists yet.
    $editor = Editor::load('filtered_html');
    $this->assertNull($editor, 'No Editor config entity exists yet.');

    // Verify the "Text Editor" <select> when a text editor is available.
    $select = $this->assertSession()->selectExists('editor[editor]');
    $this->assertFalse($select->hasAttribute('disabled'));
    $options = $select->findAll('css', 'option');
    $this->assertCount(2, $options);
    $this->assertSame('None', $options[0]->getText());
    $this->assertSame('CKEditor', $options[1]->getText());
    $this->assertTrue($options[0]->isSelected());

    // Select the "CKEditor" editor and click the "Save configuration" button.
    $edit = [
      'editor[editor]' => 'ckeditor',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('You must configure the selected text editor.');

    // Ensure the CKEditor editor returns the expected default settings.
    $expected_default_settings = [
      'toolbar' => [
        'rows' => [
          // Button groups
          [
            [
              'name' => 'Formatting',
              'items' => ['Bold', 'Italic'],
            ],
            [
              'name' => 'Links',
              'items' => ['DrupalLink', 'DrupalUnlink'],
            ],
            [
              'name' => 'Lists',
              'items' => ['BulletedList', 'NumberedList'],
            ],
            [
              'name' => 'Media',
              'items' => ['Blockquote', 'DrupalImage'],
            ],
            [
              'name' => 'Tools',
              'items' => ['Source'],
            ],
          ],
        ],
      ],
      'plugins' => [],
    ];
    $this->assertEquals($expected_default_settings, $ckeditor->getDefaultSettings());

    // Keep the "CKEditor" editor selected and click the "Configure" button.
    $this->submitForm($edit, 'editor_configure');
    $editor = Editor::load('filtered_html');
    $this->assertNull($editor, 'No Editor config entity exists yet.');

    // Ensure that drupalSettings is correct.
    $ckeditor_settings_toolbar = [
      '#theme' => 'ckeditor_settings_toolbar',
      '#editor' => Editor::create(['editor' => 'ckeditor']),
      '#plugins' => $this->container->get('plugin.manager.ckeditor.plugin')->getButtons(),
    ];
    $settings = $this->getDrupalSettings();
    $expected = $settings['ckeditor']['toolbarAdmin'];
    $this->assertEquals(
      $expected,
      $this->container->get('renderer')->renderPlain($ckeditor_settings_toolbar),
      'CKEditor toolbar settings are rendered as part of drupalSettings.'
    );

    // Ensure the toolbar buttons configuration value is initialized to the
    // expected default value.
    $expected_buttons_value = json_encode($expected_default_settings['toolbar']['rows']);
    $this->assertSession()->fieldValueEquals('editor[settings][toolbar][button_groups]', $expected_buttons_value);

    // Submit the form to save the selection of CKEditor as the chosen editor.
    $this->submitForm($edit, 'Save configuration');

    // Ensure an Editor object exists now, with the proper settings.
    $expected_settings = $expected_default_settings;
    $editor = Editor::load('filtered_html');
    $this->assertInstanceOf(Editor::class, $editor);
    $this->assertEquals($expected_settings, $editor->getSettings(), 'The Editor config entity has the correct settings.');

    // Configure the Styles plugin, and ensure the updated settings are saved.
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');

    // Ensure the styles textarea exists and is initialized empty.
    $this->assertSession()->fieldValueEquals('editor[settings][plugins][stylescombo][styles]', '');

    $expected_settings['toolbar']['rows'][0][] = [
      'name' => 'Styles dropdown',
      'items' => ['Styles'],
    ];
    $edit = [
      'editor[settings][toolbar][button_groups]' => json_encode($expected_settings['toolbar']['rows']),
      'editor[settings][plugins][stylescombo][styles]' => "h1.title|Title\np.callout|Callout\n\n",
    ];
    $this->submitForm($edit, 'Save configuration');
    $expected_settings['plugins']['stylescombo']['styles'] = "h1.title|Title\np.callout|Callout\n\n";
    $editor = Editor::load('filtered_html');
    $this->assertInstanceOf(Editor::class, $editor);
    $this->assertEquals($expected_settings, $editor->getSettings(), 'The Editor config entity has the correct settings.');

    // Change the buttons that appear on the toolbar (in JavaScript, this is
    // done via drag and drop, but here we can only emulate the end result of
    // that interaction). Test multiple toolbar rows and a divider within a row.
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $expected_settings = $expected_default_settings;
    $expected_settings['toolbar']['rows'][0][] = [
      'name' => 'Action history',
      'items' => ['Undo', '|', 'Redo', 'JustifyCenter'],
    ];
    $edit = [
      'editor[settings][toolbar][button_groups]' => json_encode($expected_settings['toolbar']['rows']),
    ];
    $this->submitForm($edit, 'Save configuration');
    $editor = Editor::load('filtered_html');
    $this->assertInstanceOf(Editor::class, $editor);
    $this->assertEquals($expected_settings, $editor->getSettings(), 'The Editor config entity has the correct settings.');

    // Check that the markup we're setting for the toolbar buttons (actually in
    // JavaScript's drupalSettings, and Unicode-escaped) is correctly rendered.
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    // Create function to encode HTML as we expect it in drupalSettings.
    $json_encode = function ($html) {
      return trim(Json::encode($html), '"');
    };
    // Check the Button separator.
    $this->assertSession()->responseContains($json_encode('<li data-drupal-ckeditor-button-name="-" class="ckeditor-button-separator ckeditor-multiple-button" data-drupal-ckeditor-type="separator"><a href="#" role="button" aria-label="Button separator" class="ckeditor-separator"></a></li>'));
    // Check the Format dropdown.
    $this->assertSession()->responseContains($json_encode('<li data-drupal-ckeditor-button-name="Format" class="ckeditor-button"><a href="#" role="button" aria-label="Format"><span class="ckeditor-button-dropdown">Format<span class="ckeditor-button-arrow"></span></span></a></li>'));
    // Check the Styles dropdown.
    $this->assertSession()->responseContains($json_encode('<li data-drupal-ckeditor-button-name="Styles" class="ckeditor-button"><a href="#" role="button" aria-label="Styles"><span class="ckeditor-button-dropdown">Styles<span class="ckeditor-button-arrow"></span></span></a></li>'));
    // Check strikethrough.
    $this->assertSession()->responseContains($json_encode('<li data-drupal-ckeditor-button-name="Strike" class="ckeditor-button"><a href="#" class="cke-icon-only cke_ltr" role="button" title="strike" aria-label="strike"><span class="cke_button_icon cke_button__strike_icon">strike</span></a></li>'));

    // Now enable the ckeditor_test module, which provides one configurable
    // CKEditor plugin — this should not affect the Editor config entity.
    \Drupal::service('module_installer')->install(['ckeditor_test']);
    $this->resetAll();
    $this->container->get('plugin.manager.ckeditor.plugin')->clearCachedDefinitions();
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $this->assertSession()->checkboxNotChecked('editor[settings][plugins][llama_contextual_and_button][ultra_llama_mode]');
    $editor = Editor::load('filtered_html');
    $this->assertInstanceOf(Editor::class, $editor);
    $this->assertEquals($expected_settings, $editor->getSettings(), 'The Editor config entity has the correct settings.');

    // Finally, check the "Ultra llama mode" checkbox.
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $expected_settings['toolbar']['rows'][0][] = [
      'name' => 'Ultra llama mode',
      'items' => ['Llama'],
    ];
    $edit = [
      'editor[settings][toolbar][button_groups]' => json_encode($expected_settings['toolbar']['rows']),
      'editor[settings][plugins][llama_contextual_and_button][ultra_llama_mode]' => '1',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $this->assertSession()->checkboxChecked('editor[settings][plugins][llama_contextual_and_button][ultra_llama_mode]');
    $expected_settings['plugins']['llama_contextual_and_button']['ultra_llama_mode'] = TRUE;
    $editor = Editor::load('filtered_html');
    $this->assertInstanceOf(Editor::class, $editor);
    $this->assertEquals($expected_settings, $editor->getSettings());

    $this->drupalGet('admin/config/content/formats/add');
    // Now attempt to add another filter format with the same editor and same
    // machine name.
    $edit = [
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'editor[editor]' => 'ckeditor',
    ];
    $this->submitForm($edit, 'editor_configure');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');
  }

  /**
   * Tests configuring a text editor for a new text format.
   *
   * This test only needs to ensure that the basics of the CKEditor
   * configuration form work; details are tested in testExistingFormat().
   */
  public function testNewFormat() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/formats/add');

    // Verify the "Text Editor" <select> when a text editor is available.
    $select = $this->assertSession()->selectExists('editor[editor]');
    $this->assertFalse($select->hasAttribute('disabled'));
    $options = $select->findAll('css', 'option');
    $this->assertCount(2, $options);
    $this->assertSame('None', $options[0]->getText());
    $this->assertSame('CKEditor', $options[1]->getText());
    $this->assertTrue($options[0]->isSelected());

    // Name our fancy new text format, select the "CKEditor" editor and click
    // the "Configure" button.
    $edit = [
      'name' => 'My amazing text format',
      'format' => 'amazing_format',
      'editor[editor]' => 'ckeditor',
    ];
    $this->submitForm($edit, 'editor_configure');
    $filter_format = FilterFormat::load('amazing_format');
    $this->assertNull($filter_format, 'No FilterFormat config entity exists yet.');
    $editor = Editor::load('amazing_format');
    $this->assertNull($editor, 'No Editor config entity exists yet.');

    // Ensure the toolbar buttons configuration value is initialized to the
    // default value.
    $ckeditor = $this->container->get('plugin.manager.editor')->createInstance('ckeditor');
    $default_settings = $ckeditor->getDefaultSettings();
    $expected_buttons_value = json_encode($default_settings['toolbar']['rows']);
    $this->assertSession()->fieldValueEquals('editor[settings][toolbar][button_groups]', $expected_buttons_value);

    // Regression test for https://www.drupal.org/node/2606460.
    $settings = $this->getDrupalSettings();
    $expected = $settings['ckeditor']['toolbarAdmin'];
    $this->assertStringContainsString('<li data-drupal-ckeditor-button-name="Bold" class="ckeditor-button"><a href="#" class="cke-icon-only cke_ltr" role="button" title="bold" aria-label="bold"><span class="cke_button_icon cke_button__bold_icon">bold</span></a></li>', $expected);

    // Ensure the styles textarea exists and is initialized empty.
    $this->assertSession()->fieldValueEquals('editor[settings][plugins][stylescombo][styles]', '');

    // Submit the form to create both a new text format and an associated text
    // editor.
    $this->submitForm($edit, 'Save configuration');

    // Ensure a FilterFormat object exists now.
    $filter_format = FilterFormat::load('amazing_format');
    $this->assertInstanceOf(FilterFormatInterface::class, $filter_format);

    // Ensure an Editor object exists now, with the proper settings.
    $expected_settings = $default_settings;
    $editor = Editor::load('amazing_format');
    $this->assertInstanceOf(Editor::class, $editor);
    $this->assertEquals($expected_settings, $editor->getSettings(), 'The Editor config entity has the correct settings.');
  }

}
