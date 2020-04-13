<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the installation of themes.
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
    $this->adminUser = $this->drupalCreateUser(['access administration pages', 'administer themes']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests installing experimental themes and dependencies in the UI.
   */
  public function testExperimentalConfirmForm() {
    // Only experimental themes should be marked as such with a parenthetical.
    $this->drupalGet('admin/appearance');
    $this->assertText(sprintf('Experimental test %s                (experimental theme)', \Drupal::VERSION));
    $this->assertText(sprintf('Experimental dependency test %s', \Drupal::VERSION));

    // First, test installing a non-experimental theme with no dependencies.
    // There should be no confirmation form and no experimental theme warning.
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install <strong>Test theme</strong> theme"]')[0]->click();
    $this->assertText('The &lt;strong&gt;Test theme&lt;/strong&gt; theme has been installed.');
    $this->assertNoText('Experimental modules are provided for testing purposes only.');

    // Next, test installing an experimental theme with no dependencies.
    // There should be a confirmation form with an experimental warning, but no
    // list of dependencies.
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install Experimental test theme"]')[0]->click();
    $this->assertText('Experimental themes are provided for testing purposes only. Use at your own risk.');

    // The module should not be enabled and there should be a warning and a
    // list of the experimental modules with only this one.
    $this->assertNoText('The Experimental Test theme has been installed.');
    $this->assertText('Experimental themes are provided for testing purposes only.');

    // There should be no message about enabling dependencies.
    $this->assertNoText('You must enable');

    // Enable the theme and confirm that it worked.
    $this->drupalPostForm(NULL, [], 'Continue');
    $this->assertText('The Experimental test theme has been installed.');

    // Setting it as the default should not ask for another confirmation.
    $this->cssSelect('a[title="Set Experimental test as default theme"]')[0]->click();
    $this->assertNoText('Experimental themes are provided for testing purposes only. Use at your own risk.');
    $this->assertText('Experimental test is now the default theme.');
    $this->assertNoText(sprintf('Experimental test %s                (experimental theme)', \Drupal::VERSION));
    $this->assertText(sprintf('Experimental test %s                (default theme, administration theme, experimental theme)', \Drupal::VERSION));

    // Uninstall the theme.
    $this->config('system.theme')->set('default', 'test_theme')->save();
    \Drupal::service('theme_handler')->refreshInfo();
    \Drupal::service('theme_installer')->uninstall(['experimental_theme_test']);

    // Reinstall the same experimental theme, but this time immediately set it
    // as the default. This should again trigger a confirmation form with an
    // experimental warning.
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install Experimental test as default theme"]')[0]->click();
    $this->assertText('Experimental themes are provided for testing purposes only. Use at your own risk.');

    // Test enabling a theme that is not itself experimental, but that depends
    // on an experimental module.
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install Experimental dependency test theme"]')[0]->click();

    // The theme should not be enabled and there should be a warning and a
    // list of the experimental modules with only this one.
    $this->assertNoText('The Experimental dependency test theme has been installed.');
    $this->assertText('Experimental themes are provided for testing purposes only. Use at your own risk.');
    $this->assertText('The following themes are experimental: Experimental test');

    // Ensure the non-experimental theme is not listed as experimental.
    $this->assertNoText('The following themes are experimental: Experimental test, Experimental dependency test');
    $this->assertNoText('The following themes are experimental: Experimental dependency test');

    // There should be a message about enabling dependencies.
    $this->assertText('You must enable the Experimental test theme to install Experimental dependency test');

    // Enable the theme and confirm that it worked.
    $this->drupalPostForm(NULL, [], 'Continue');
    $this->assertText('The Experimental dependency test theme has been installed.');
    $this->assertText(sprintf('Experimental test %s                (experimental theme)', \Drupal::VERSION));
    $this->assertText(sprintf('Experimental dependency test %s', \Drupal::VERSION));

    // Setting it as the default should not ask for another confirmation.
    $this->cssSelect('a[title="Set Experimental dependency test as default theme"]')[0]->click();
    $this->assertNoText('Experimental themes are provided for testing purposes only. Use at your own risk.');
    $this->assertText('Experimental dependency test is now the default theme.');
    $this->assertText(sprintf('Experimental test %s                (experimental theme)', \Drupal::VERSION));
    $this->assertText(sprintf('Experimental dependency test %s                (default theme, administration theme)', \Drupal::VERSION));

    // Uninstall the theme.
    $this->config('system.theme')->set('default', 'test_theme')->save();
    \Drupal::service('theme_handler')->refreshInfo();
    \Drupal::service('theme_installer')->uninstall(['experimental_theme_test', 'experimental_theme_dependency_test']);

    // Reinstall the same theme, but this time immediately set it as the
    // default. This should again trigger a confirmation form with an
    // experimental warning for its dependency.
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install Experimental dependency test as default theme"]')[0]->click();
    $this->assertText('Experimental themes are provided for testing purposes only. Use at your own risk.');
  }

}
