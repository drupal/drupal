<?php

namespace Drupal\FunctionalTests\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Olivero theme.
 *
 * @group olivero
 */
class OliveroTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'block'];

  /**
   * {@inheritdoc}
   *
   * It should eventually be possible to set this to 'olivero' once the theme
   * is in core.
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install & set Olivero as default the theme.
    // Note: it should be possible to remove this once Olivero can be set as
    // the default theme above.
    $this->container->get('theme_installer')->install(['olivero'], TRUE);
    $system_theme_config = $this->container->get('config.factory')->getEditable('system.theme');
    $system_theme_config
      ->set('default', 'olivero')
      ->save();
  }

  /**
   * Tests that the Olivero theme always adds base library files.
   *
   * @see olivero.libraries.yml
   */
  public function testBaseLibraryAvailable() {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('olivero/css/base/base.css');
    $this->assertSession()->responseContains('olivero/js/scripts.js');
  }

  /**
   * Test Olivero's configuration schema.
   */
  public function testConfigSchema() {
    // Required configuration.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', '#block-olivero-content');
    $this->assertSession()->elementNotExists('css', '#block-olivero-search-form-wide');

    // Optional configuration.
    \Drupal::service('module_installer')->install(
      ['search', 'image', 'book', 'help', 'node']
    );
    $this->rebuildAll();
    $this->drupalLogin(
      $this->drupalCreateUser(['search content'])
    );

    // Confirm search block was installed.
    $this->assertSession()->elementExists('css', '#block-olivero-search-form-wide');
  }

  /**
   * Tests that the Olivero theme can be uninstalled.
   */
  public function testIsUninstallable() {
    $this->drupalLogin($this->drupalCreateUser(['access administration pages', 'administer themes']));

    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install Bartik as default theme"]')[0]->click();
    $this->cssSelect('a[title="Uninstall Olivero theme"]')[0]->click();
    $this->assertText('The Olivero theme has been uninstalled.');
  }

}
