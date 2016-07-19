<?php

namespace Drupal\ckeditor\Tests;

use Drupal\editor\Entity\Editor;
use Drupal\simpletest\WebTestBase;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests administration of the CKEditor StylesCombo plugin.
 *
 * @group ckeditor
 */
class CKEditorStylesComboAdminTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'editor', 'ckeditor'];

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->format = strtolower($this->randomMachineName());
    $filter_format = FilterFormat::create([
      'format' => $this->format,
      'name' => $this->randomString(),
      'filters' => [],
    ]);
    $filter_format->save();
    $editor = Editor::create([
      'format' => $this->format,
      'editor' => 'ckeditor',
    ]);
    $editor->save();

    $this->adminUser = $this->drupalCreateUser(['administer filters']);
  }

  /**
   * Tests StylesCombo settings for an existing text format.
   */
  function testExistingFormat() {
    $ckeditor = $this->container->get('plugin.manager.editor')->createInstance('ckeditor');
    $default_settings = $ckeditor->getDefaultSettings();

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/formats/manage/' . $this->format);

    // Ensure an Editor config entity exists, with the proper settings.
    $expected_settings = $default_settings;
    $editor = Editor::load($this->format);
    $this->assertEqual($expected_settings, $editor->getSettings(), 'The Editor config entity has the correct settings.');

    // Case 1: Configure the Styles plugin with different labels for each style,
    // and ensure the updated settings are saved.
    $this->drupalGet('admin/config/content/formats/manage/' . $this->format);
    $edit = [
      'editor[settings][plugins][stylescombo][styles]' => "h1.title|Title\np.callout|Callout\n\n",
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $expected_settings['plugins']['stylescombo']['styles'] = "h1.title|Title\np.callout|Callout\n\n";
    $editor = Editor::load($this->format);
    $this->assertEqual($expected_settings, $editor->getSettings(), 'The Editor config entity has the correct settings.');

    // Case 2: Configure the Styles plugin with same labels for each style, and
    // ensure that an error is displayed and that the updated settings are not
    // saved.
    $this->drupalGet('admin/config/content/formats/manage/' . $this->format);
    $edit = [
      'editor[settings][plugins][stylescombo][styles]' => "h1.title|Title\np.callout|Title\n\n",
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertRaw(t('Each style must have a unique label.'));
    $expected_settings['plugins']['stylescombo']['styles'] = "h1.title|Title\np.callout|Callout\n\n";
    $editor = Editor::load($this->format);
    $this->assertEqual($expected_settings, $editor->getSettings(), 'The Editor config entity has the correct settings.');
  }

}
