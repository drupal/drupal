<?php

namespace Drupal\Tests\ckeditor\Functional;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests administration of the CKEditor StylesCombo plugin.
 *
 * @group ckeditor
 */
class CKEditorStylesComboAdminTest extends BrowserTestBase {

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

  /**
   * A random generated format machine name.
   *
   * @var string
   */
  protected $format;

  /**
   * The default editor settings.
   *
   * @var array
   */
  protected $defaultSettings;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->format = strtolower($this->randomMachineName());
    $filter_format = FilterFormat::create([
      'format' => $this->format,
      'name' => $this->randomString(),
      'filters' => [],
    ]);
    $filter_format->save();
    $ckeditor = $this->container->get('plugin.manager.editor')->createInstance('ckeditor');
    $this->defaultSettings = $ckeditor->getDefaultSettings();
    $this->defaultSettings['toolbar']['rows'][0][] = [
      'name' => 'Styles dropdown',
      'items' => ['Styles'],
    ];
    $editor = Editor::create([
      'format' => $this->format,
      'editor' => 'ckeditor',
      'settings' => $this->defaultSettings,
    ]);
    $editor->save();

    $this->adminUser = $this->drupalCreateUser(['administer filters']);
  }

  /**
   * Tests StylesCombo settings for an existing text format.
   */
  public function testExistingFormat() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/formats/manage/' . $this->format);

    // Ensure an Editor config entity exists, with the proper settings.
    $expected_settings = $this->defaultSettings;
    $editor = Editor::load($this->format);
    $this->assertEquals($expected_settings, $editor->getSettings(), 'The Editor config entity has the correct settings.');

    // Case 1: Configure the Styles plugin with different labels for each style,
    // and ensure the updated settings are saved.
    $this->drupalGet('admin/config/content/formats/manage/' . $this->format);
    $edit = [
      'editor[settings][plugins][stylescombo][styles]' => "h1.title|Title\np.callout|Callout\ndrupal-entity.has-dashes|Allowing Dashes\n\n",
    ];
    $this->submitForm($edit, 'Save configuration');
    $expected_settings['plugins']['stylescombo']['styles'] = "h1.title|Title\np.callout|Callout\ndrupal-entity.has-dashes|Allowing Dashes\n\n";
    $editor = Editor::load($this->format);
    $this->assertEquals($expected_settings, $editor->getSettings(), 'The Editor config entity has the correct settings.');

    // Case 2: Configure the Styles plugin with same labels for each style, and
    // ensure that an error is displayed and that the updated settings are not
    // saved.
    $this->drupalGet('admin/config/content/formats/manage/' . $this->format);
    $edit = [
      'editor[settings][plugins][stylescombo][styles]' => "h1.title|Title\np.callout|Title\n\n",
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('Each style must have a unique label.');
    $editor = Editor::load($this->format);
    $this->assertEquals($expected_settings, $editor->getSettings(), 'The Editor config entity has the correct settings.');
  }

}
