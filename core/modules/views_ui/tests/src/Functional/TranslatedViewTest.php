<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Functional;

use Drupal\language\Entity\ConfigurableLanguage;

// cspell:ignore fichiers

/**
 * Tests that translated strings in views UI don't override original strings.
 *
 * @group views_ui
 */
class TranslatedViewTest extends UITestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'config_translation',
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = []): void {
    parent::setUp($import_test_views, $modules);

    $permissions = [
      'administer site configuration',
      'administer views',
      'translate configuration',
      'translate interface',
    ];

    $this->drupalPlaceBlock('local_tasks_block', ['id' => 'test_role_admin_test_local_tasks_block']);

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

  public function testTranslatedStrings(): void {
    $translation_url = 'admin/structure/views/view/files/translate/fr/add';
    $edit_url = 'admin/structure/views/view/files';

    // Check the original string.
    $this->drupalGet($edit_url);
    $this->assertSession()->titleEquals('Files (File) | Drupal');

    // Translate the label of the view.
    $this->drupalGet($translation_url);
    $edit = [
      'translation[config_names][views.view.files][label]' => 'Fichiers',
    ];
    $this->submitForm($edit, 'Save translation');

    // Check if the label is translated.
    $this->drupalGet($edit_url, ['language' => \Drupal::languageManager()->getLanguage('fr')]);
    $this->assertSession()->titleEquals('Files (File) | Drupal');
    $this->assertSession()->pageTextNotContains('Fichiers');

    // Ensure that "Link URL" and "Link Path" fields are translatable.
    // First, Add the block display and change pager's 'link display' to
    // custom URL.
    // Second, change filename to use plain text and rewrite output with link.
    $this->drupalGet($edit_url);
    $this->submitForm([], 'Add Block');
    $this->drupalGet('admin/structure/views/nojs/display/files/block_1/link_display');
    $edit = [
      'link_display' => 'custom_url',
      'link_url' => '/node',
    ];
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');
    $this->drupalGet('admin/structure/views/nojs/handler/files/block_1/field/filename');
    $edit = [
      'override[dropdown]' => 'block_1',
      'options[type]' => 'string',
      'options[alter][path]' => '/node',
      'options[alter][make_link]' => 1,
    ];
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');

    // Visit the translation page and ensure that field exists.
    $this->drupalGet($translation_url);
    $this->assertSession()->fieldExists('translation[config_names][views.view.files][display][block_1][display_options][fields][filename][alter][path]');
    $this->assertSession()->fieldExists('translation[config_names][views.view.files][display][default][display_options][link_url]');

    // Assert that the View translation link is shown when viewing a display.
    $this->drupalGet($edit_url);
    $this->assertSession()->linkExists('Translate view');
    $this->drupalGet('/admin/structure/views/view/files/edit/block_1');
    $this->assertSession()->linkExists('Translate view');
  }

}
