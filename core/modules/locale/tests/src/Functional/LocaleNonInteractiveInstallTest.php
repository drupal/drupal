<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\Locale\InstallerLanguageTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests installing in a different language with a non-dev version string.
 */
#[Group('locale')]
#[RunTestsInSeparateProcesses]
class LocaleNonInteractiveInstallTest extends BrowserTestBase {
  use InstallerLanguageTrait;
  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function getInstallLangcode(): string {
    return 'de';
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslationFileContents(): string {
    return <<<PO
msgid ""
msgstr ""

msgid "Log in"
msgstr "Anmelden"

PO;
  }

  /**
   * Tests that the expected translated text appears on the login screen.
   */
  public function testInstallerTranslations(): void {
    $this->drupalGet('user/login');
    // cSpell:disable-next-line
    $this->assertSession()->responseContains('Anmelden');
  }

}
