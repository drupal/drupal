<?php

declare(strict_types=1);

namespace Drupal\Tests\content_translation\Functional;

use Drupal\content_translation\Hook\ContentTranslationFormLanguageHooks;
use Drupal\language\Form\ContentLanguageSettingsForm;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the content translation UI check skip.
 */
#[Group('content_translation')]
#[CoversClass(ContentLanguageSettingsForm::class)]
#[CoversClass(ContentTranslationFormLanguageHooks::class)]
#[RunTestsInSeparateProcesses]
class ContentTranslationUISkipTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
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
