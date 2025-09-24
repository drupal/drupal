<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that the language settings on block config appears correctly.
 */
#[Group('language')]
#[RunTestsInSeparateProcesses]
class LanguageBlockSettingsVisibilityTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests languages displayed in the language switcher.
   */
  public function testUnnecessaryLanguageSettingsVisibility(): void {
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
      'administer blocks',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm(['predefined_langcode' => 'hu'], 'Add language');
    $this->drupalGet('admin/structure/block/add/system_menu_block:admin/stark');
    $this->assertSession()->fieldNotExists("edit-visibility-language-langcodes-und");
    $this->assertSession()->fieldNotExists("edit-visibility-language-langcodes-zxx");
    $this->assertSession()->fieldExists("edit-visibility-language-langcodes-en");
    $this->assertSession()->fieldExists("edit-visibility-language-langcodes-hu");
  }

}
