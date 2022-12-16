<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the installation of experimental themes.
 *
 * @group Theme
 */
class ExperimentalThemeTest extends BrowserTestBase {

  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer themes',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests installing experimental themes and dependencies in the UI.
   */
  public function testExperimentalConfirmForm(): void {
    // Only experimental themes should be marked as such with a parenthetical.
    $this->drupalGet('admin/appearance');
    $this->assertSession()->responseContains(sprintf('Experimental test %s                (experimental theme)', \Drupal::VERSION));
    $this->assertSession()->responseContains(sprintf('Experimental dependency test %s', \Drupal::VERSION));

    // First, test installing a non-experimental theme with no dependencies.
    // There should be no confirmation form and no experimental theme warning.
    $this->cssSelect('a[title="Install <strong>Test theme</strong> theme"]')[0]->click();
    $this->assertSession()->pageTextContains('The <strong>Test theme</strong> theme has been installed.');
    $this->assertSession()->pageTextNotContains('Experimental modules are provided for testing purposes only.');

    // Next, test installing an experimental theme with no dependencies.
    // There should be a confirmation form with an experimental warning, but no
    // list of dependencies.
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install Experimental test theme"]')[0]->click();
    $this->assertSession()->pageTextContains('Experimental themes are provided for testing purposes only. Use at your own risk.');

    // The module should not be enabled and there should be a warning and a
    // list of the experimental modules with only this one.
    $this->assertSession()->pageTextNotContains('The Experimental test theme has been installed.');
    $this->assertSession()->pageTextContains('Experimental themes are provided for testing purposes only.');

    // There should be no message about enabling dependencies.
    $this->assertSession()->pageTextNotContains('You must enable');

    // Enable the theme and confirm that it worked.
    $this->submitForm([], 'Continue');
    $this->assertSession()->pageTextContains('The Experimental test theme has been installed.');

    // Setting it as the default should not ask for another confirmation.
    $this->cssSelect('a[title="Set Experimental test as default theme"]')[0]->click();
    $this->assertSession()->pageTextNotContains('Experimental themes are provided for testing purposes only. Use at your own risk.');
    $this->assertSession()->pageTextContains('Experimental test is now the default theme.');
    $this->assertSession()->pageTextNotContains(sprintf('Experimental test %s                (experimental theme)', \Drupal::VERSION));
    $this->assertSession()->responseContains(sprintf('Experimental test %s                (default theme, administration theme, experimental theme)', \Drupal::VERSION));

    // Uninstall the theme.
    $this->config('system.theme')->set('default', 'test_theme')->save();
    \Drupal::service('theme_handler')->refreshInfo();
    \Drupal::service('theme_installer')->uninstall(['experimental_theme_test']);

    // Reinstall the same experimental theme, but this time immediately set it
    // as the default. This should again trigger a confirmation form with an
    // experimental warning.
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install Experimental test as default theme"]')[0]->click();
    $this->assertSession()->pageTextContains('Experimental themes are provided for testing purposes only. Use at your own risk.');

    // Test enabling a theme that is not itself experimental, but that depends
    // on an experimental module.
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install Experimental dependency test theme"]')[0]->click();

    // The theme should not be enabled and there should be a warning and a
    // list of the experimental modules with only this one.
    $this->assertSession()->pageTextNotContains('The Experimental dependency test theme has been installed.');
    $this->assertSession()->pageTextContains('Experimental themes are provided for testing purposes only. Use at your own risk.');
    $this->assertSession()->pageTextContains('The following themes are experimental: Experimental test');

    // Ensure the non-experimental theme is not listed as experimental.
    $this->assertSession()->pageTextNotContains('The following themes are experimental: Experimental test, Experimental dependency test');
    $this->assertSession()->pageTextNotContains('The following themes are experimental: Experimental dependency test');

    // There should be a message about enabling dependencies.
    $this->assertSession()->pageTextContains('You must enable the Experimental test theme to install Experimental dependency test');

    // Enable the theme and confirm that it worked.
    $this->submitForm([], 'Continue');
    $this->assertSession()->pageTextContains('The Experimental dependency test theme has been installed.');
    $this->assertSession()->responseContains(sprintf('Experimental test %s                (experimental theme)', \Drupal::VERSION));
    $this->assertSession()->responseContains(sprintf('Experimental dependency test %s', \Drupal::VERSION));

    // Setting it as the default should not ask for another confirmation.
    $this->cssSelect('a[title="Set Experimental dependency test as default theme"]')[0]->click();
    $this->assertSession()->pageTextNotContains('Experimental themes are provided for testing purposes only. Use at your own risk.');
    $this->assertSession()->pageTextContains('Experimental dependency test is now the default theme.');
    $this->assertSession()->responseContains(sprintf('Experimental test %s                (experimental theme)', \Drupal::VERSION));
    $this->assertSession()->responseContains(sprintf('Experimental dependency test %s                (default theme, administration theme)', \Drupal::VERSION));

    // Uninstall the theme.
    $this->config('system.theme')->set('default', 'test_theme')->save();
    \Drupal::service('theme_handler')->refreshInfo();
    \Drupal::service('theme_installer')->uninstall(
      ['experimental_theme_test', 'experimental_theme_dependency_test']
    );

    // Reinstall the same theme, but this time immediately set it as the
    // default. This should again trigger a confirmation form with an
    // experimental warning for its dependency.
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install Experimental dependency test as default theme"]')[0]->click();
    $this->assertSession()->pageTextContains('Experimental themes are provided for testing purposes only. Use at your own risk.');
  }

}
