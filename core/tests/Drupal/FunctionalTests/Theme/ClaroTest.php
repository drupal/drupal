<?php

namespace Drupal\FunctionalTests\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Claro theme.
 *
 * @group claro
 */
class ClaroTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * Install the shortcut module so that claro.settings has its schema checked.
   * There's currently no way for Claro to provide a default and have valid
   * configuration as themes cannot react to a module install.
   *
   * @var string[]
   */
  public static $modules = ['shortcut'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->assertTrue(\Drupal::service('theme_installer')->install(['claro']));
    $this->container->get('config.factory')
      ->getEditable('system.theme')
      ->set('default', 'claro')
      ->save();
  }

  /**
   * Tests that the Claro theme always adds its elements.css.
   *
   * @see claro.info.yml
   */
  public function testRegressionMissingElementsCss() {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('claro/css/src/base/elements.css');

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/modules');
    $this->assertSession()->elementNotExists('css', '#block-claro-help');

    // Install the block module to ensure Claro's configuration is valid
    // according to schema.
    \Drupal::service('module_installer')->install(['block', 'help']);
    $this->rebuildAll();

    $this->drupalGet('admin/modules');
    $this->assertSession()->elementExists('css', '#block-claro-help');
  }

  /**
   * Tests that the Claro theme can be uninstalled, despite being experimental.
   *
   * @todo Remove in https://www.drupal.org/project/drupal/issues/3066007
   */
  public function testIsUninstallable() {
    $this->drupalLogin($this->drupalCreateUser(['access administration pages', 'administer themes']));

    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install Seven as default theme"]')[0]->click();
    $this->cssSelect('a[title="Uninstall Claro theme"]')[0]->click();
    $this->assertText('The Claro theme has been uninstalled.');
  }

}
