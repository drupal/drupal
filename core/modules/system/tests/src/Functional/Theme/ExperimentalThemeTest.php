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
   *
   * @dataProvider providerTestExperimentalConfirmForm
   */
  public function testExperimentalConfirmForm(string $theme_name, string $dependency_theme_name, string $machine_theme_name, string $machine_dependency_theme_name): void {
    // Only experimental themes should be marked as such with a parenthetical.
    $this->drupalGet('admin/appearance');
    $this->assertSession()->responseContains(sprintf($theme_name . ' %s                (experimental theme)', \Drupal::VERSION));
    $this->assertSession()->responseContains(sprintf($dependency_theme_name . ' %s', \Drupal::VERSION));

    // First, test installing a non-experimental theme with no dependencies.
    // There should be no confirmation form and no experimental theme warning.
    $this->cssSelect('a[title="Install <strong>Test theme</strong> theme"]')[0]->click();
    $this->assertSession()->pageTextContains('The <strong>Test theme</strong> theme has been installed.');
    $this->assertSession()->pageTextNotContains('Experimental modules are provided for testing purposes only.');

    // Next, test installing an experimental theme with no dependencies.
    // There should be a confirmation form with an experimental warning, but no
    // list of dependencies.
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install ' . $theme_name . ' theme"]')[0]->click();
    $this->assertSession()->pageTextContains('Experimental themes are provided for testing purposes only. Use at your own risk.');

    // The module should not be enabled and there should be a warning and a
    // list of the experimental modules with only this one.
    $this->assertSession()->pageTextNotContains('The ' . $theme_name . ' theme has been installed.');
    $this->assertSession()->pageTextContains('Experimental themes are provided for testing purposes only.');

    // There should be no message about enabling dependencies.
    $this->assertSession()->pageTextNotContains('You must enable');

    // Enable the theme and confirm that it worked.
    $this->submitForm([], 'Continue');
    $this->assertSession()->pageTextContains('The ' . $theme_name . ' theme has been installed.');

    // Setting it as the default should not ask for another confirmation.
    $this->cssSelect('a[title="Set ' . $theme_name . ' as default theme"]')[0]->click();
    $this->assertSession()->pageTextNotContains('Experimental themes are provided for testing purposes only. Use at your own risk.');
    $this->assertSession()->pageTextContains($theme_name . ' is now the default theme.');
    $this->assertSession()->pageTextNotContains(sprintf($theme_name . ' %s                (experimental theme)', \Drupal::VERSION));
    $this->assertSession()->responseContains(sprintf($theme_name . ' %s                (default theme, administration theme, experimental theme)', \Drupal::VERSION));

    // Uninstall the theme.
    $this->config('system.theme')->set('default', 'test_theme')->save();
    \Drupal::service('theme_handler')->refreshInfo();
    \Drupal::service('theme_installer')->uninstall([$machine_theme_name]);

    // Reinstall the same experimental theme, but this time immediately set it
    // as the default. This should again trigger a confirmation form with an
    // experimental warning.
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install ' . $theme_name . ' as default theme"]')[0]->click();
    $this->assertSession()->pageTextContains('Experimental themes are provided for testing purposes only. Use at your own risk.');

    // Test enabling a theme that is not itself experimental, but that depends
    // on an experimental module.
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install ' . $dependency_theme_name . ' theme"]')[0]->click();

    // The theme should not be enabled and there should be a warning and a
    // list of the experimental modules with only this one.
    $this->assertSession()->pageTextNotContains('The ' . $dependency_theme_name . ' theme has been installed.');
    $this->assertSession()->pageTextContains('Experimental themes are provided for testing purposes only. Use at your own risk.');
    $this->assertSession()->pageTextContains('The following themes are experimental: ' . $theme_name);

    // Ensure the non-experimental theme is not listed as experimental.
    $this->assertSession()->pageTextNotContains('The following themes are experimental: ' . $theme_name . ', ' . $dependency_theme_name);
    $this->assertSession()->pageTextNotContains('The following themes are experimental: ' . $dependency_theme_name);

    // There should be a message about enabling dependencies.
    $this->assertSession()->pageTextContains('You must enable the ' . $theme_name . ' theme to install ' . $dependency_theme_name);

    // Enable the theme and confirm that it worked.
    $this->submitForm([], 'Continue');
    $this->assertSession()->pageTextContains('The ' . $dependency_theme_name . ' theme has been installed.');
    $this->assertSession()->responseContains(sprintf($theme_name . ' %s                (experimental theme)', \Drupal::VERSION));
    $this->assertSession()->responseContains(sprintf($dependency_theme_name . ' %s', \Drupal::VERSION));

    // Setting it as the default should not ask for another confirmation.
    $this->cssSelect('a[title="Set ' . $dependency_theme_name . ' as default theme"]')[0]->click();
    $this->assertSession()->pageTextNotContains('Experimental themes are provided for testing purposes only. Use at your own risk.');
    $this->assertSession()->pageTextContains($dependency_theme_name . ' is now the default theme.');
    $this->assertSession()->responseContains(sprintf($theme_name . ' %s                (experimental theme)', \Drupal::VERSION));
    $this->assertSession()->responseContains(sprintf($dependency_theme_name . ' %s                (default theme, administration theme)', \Drupal::VERSION));

    // Uninstall the theme.
    $this->config('system.theme')->set('default', 'test_theme')->save();
    \Drupal::service('theme_handler')->refreshInfo();
    \Drupal::service('theme_installer')->uninstall(
      [$machine_theme_name, $machine_dependency_theme_name]
    );

    // Reinstall the same theme, but this time immediately set it as the
    // default. This should again trigger a confirmation form with an
    // experimental warning for its dependency.
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install ' . $dependency_theme_name . ' as default theme"]')[0]->click();
    $this->assertSession()->pageTextContains('Experimental themes are provided for testing purposes only. Use at your own risk.');
  }

  /**
   * Data provider for experimental test themes.
   *
   * @return string[][]
   *   An array with four items:
   *   - The theme name.
   *   - The dependency theme name.
   *   - The machine theme name.
   *   - The machine dependency theme name.
   *
   * @todo Turn the check for 'Testing legacy Key/Value pair
   * "experimental: true"' into a @legacy test triggering a deprecation as part
   * of https://www.drupal.org/node/3250342
   */
  public function providerTestExperimentalConfirmForm(): array {
    return [
      'Testing Key/Value pair "lifecycle: experimental"' =>
        [
          'Experimental test',
          'Experimental dependency test',
          'experimental_theme_test',
          'experimental_theme_dependency_test',
        ],
      'Testing legacy Key/Value pair "experimental: true"' =>
        [
          'Legacy experimental test',
          'Legacy experimental dependency test',
          'legacy_experimental_theme_test',
          'legacy_experimental_theme_dependency_test',
        ],
    ];
  }

}
