<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that locale rewrites config langcodes after theme install.
 *
 * When a theme ships config with langcode 'en' and locale is enabled with a
 * non-English site default, locale's updateDefaultConfigLangcodes() batch
 * must rewrite those langcodes to the site default after install.
 */
#[Group('locale')]
#[RunTestsInSeparateProcesses]
class LocaleThemeInstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'locale'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function installParameters(): array {
    $parameters = parent::installParameters();
    $parameters['parameters']['langcode'] = 'de';
    // Create a po file so we don't attempt to download one from
    // localize.drupal.org and to have a test translation that will not change.
    \Drupal::service('file_system')->mkdir($this->publicFilesDirectory . '/translations', NULL, TRUE);
    $contents = <<<PO
msgid ""
msgstr ""

PO;
    file_put_contents($this->publicFilesDirectory . '/translations/drupal-' . \Drupal::VERSION . '.de.po', $contents);
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $admin = $this->drupalCreateUser([
      'administer themes',
      'administer languages',
      'access administration pages',
    ]);
    $this->drupalLogin($admin);
  }

  /**
   * Tests that installing a theme via the UI updates config language.
   *
   * The olivero theme ships core.date_format.olivero_medium with langcode
   * 'en'. With locale enabled and a non-English site default, that langcode
   * should be rewritten to the site default after the install batch runs.
   */
  public function testThemeInstallRewritesConfigLangcode(): void {
    // Install olivero via the Appearance UI.
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install Olivero theme"]')[0]->click();
    // The ThemeController redirects to the batch page; follow its meta-refresh
    // chain to process all batch operations before asserting config state.
    $this->checkForMetaRefresh();
    $this->rebuildContainer();

    // core.date_format.olivero_medium ships with langcode 'en'. Locale's
    // updateDefaultConfigLangcodes() should have rewritten it to 'de'.
    $langcode = $this->config('core.date_format.olivero_medium')->get('langcode');
    $this->assertSame('de', $langcode);

    // Ensure that the theme is installed.
    $this->assertSession()->addressEquals('admin/appearance');
    $this->assertSession()->pageTextContains('The Olivero theme has been installed.');
  }

  /**
   * Tests that "install as default" also updates config language.
   */
  public function testThemeSetDefaultRewritesConfigLangcode(): void {
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install Olivero as default theme"]')[0]->click();
    $this->checkForMetaRefresh();
    $this->rebuildContainer();
    $langcode = $this->config('core.date_format.olivero_medium')->get('langcode');
    $this->assertSame('de', $langcode);
    $this->assertSession()->addressEquals('admin/appearance');
    $this->assertSession()->pageTextContains('Olivero is now the default theme.');
  }

}
