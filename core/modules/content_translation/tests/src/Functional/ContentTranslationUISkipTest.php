<?php

declare(strict_types=1);

namespace Drupal\Tests\content_translation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the content translation UI check skip.
 *
 * @covers \Drupal\language\Form\ContentLanguageSettingsForm
 * @covers ::_content_translation_form_language_content_settings_form_alter
 * @group content_translation
 */
class ContentTranslationUISkipTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['content_translation_test', 'user', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the content_translation_ui_skip key functionality.
   */
  public function testUICheckSkip(): void {
    $admin_user = $this->drupalCreateUser([
      'translate any entity',
      'administer content translation',
      'administer languages',
    ]);
    $this->drupalLogin($admin_user);
    // Visit the content translation.
    $this->drupalGet('admin/config/regional/content-language');

    // Check the message regarding UI integration.
    $this->assertSession()->pageTextContains('Test entity - Translatable skip UI check');
    $this->assertSession()->pageTextContains('Test entity - Translatable check UI (Translation is not supported)');
  }

}
