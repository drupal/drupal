<?php

namespace Drupal\views_ui\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests that translated strings in views UI don't override original strings.
 *
 * @group views_ui
 */
class TranslatedViewTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'config_translation',
    'views_ui',
  ];

  /**
   * Languages to enable.
   *
   * @var array
   */
  protected $langcodes = [
    'fr',
  ];

  /**
   * Administrator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    $permissions = [
      'administer site configuration',
      'administer views',
      'translate configuration',
      'translate interface',
    ];

    // Create and log in user.
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);

    // Add languages.
    foreach ($this->langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    $this->resetAll();
    $this->rebuildContainer();
  }

  public function testTranslatedStrings() {
    $translation_url = 'admin/structure/views/view/files/translate/fr/add';
    $edit_url = 'admin/structure/views/view/files';

    // Check origial string.
    $this->drupalGet($edit_url);
    $this->assertTitle('Files (File) | Drupal');

    // Translate the label of the view.
    $this->drupalGet($translation_url);
    $edit = [
      'translation[config_names][views.view.files][label]' => 'Fichiers',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save translation'));

    // Check if the label is translated.
    $this->drupalGet($edit_url, ['language' => \Drupal::languageManager()->getLanguage('fr')]);
    $this->assertTitle('Files (File) | Drupal');
    $this->assertNoText('Fichiers');
  }

}
