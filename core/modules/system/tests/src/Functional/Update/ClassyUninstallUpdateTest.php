<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Ensures that update hook uninstalls Classy when it's no longer needed.
 *
 * @group Update
 * @group legacy
 * @see system_post_update_uninstall_classy()
 */
class ClassyUninstallUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
  }

  /**
   * Ensures that Classy is disabled if it's no longer needed.
   */
  public function testUpdate() {
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = $this->container->get('theme_handler');
    $this->assertTrue($theme_handler->themeExists('classy'));
    $this->assertTrue($theme_handler->themeExists('seven'));

    $this->runUpdates();

    // Ensure that Classy is not installed after running updates.
    $theme_handler->refreshInfo();
    $this->assertFalse($theme_handler->themeExists('classy'));
    $this->assertTrue($theme_handler->themeExists('seven'));
  }

  /**
   * Ensures that updates run without errors when Classy is not installed.
   */
  public function testUpdateClassyNotInstalled() {
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = $this->container->get('theme_handler');
    $theme_list = array_keys($theme_handler->listInfo());
    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = $this->container->get('theme_installer');
    $theme_installer->install(['stark']);
    $this->container->get('config.factory')
      ->getEditable('system.theme')
      ->set('default', 'stark')
      ->set('admin', '')
      ->save();
    $theme_handler->refreshInfo();

    // Uninstall all themes that were installed prior to enabling Stark.
    $theme_installer->uninstall($theme_list);

    // Ensure that Classy is not installed anymore.
    $theme_handler->refreshInfo();
    $this->assertFalse($theme_handler->themeExists('classy'));

    $this->runUpdates();

    $theme_handler->refreshInfo();
    $this->assertFalse($theme_handler->themeExists('classy'));
  }

  /**
   * Ensures that updates run without errors when Classy is still needed.
   */
  public function testUpdateClassyNeeded() {
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = $this->container->get('theme_handler');
    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = $this->container->get('theme_installer');
    $theme_installer->install(['test_legacy_stylesheets_remove']);
    $this->assertTrue($theme_handler->themeExists('classy'));

    $this->runUpdates();

    // Ensure that Classy is still installed after running tests.
    $theme_handler->refreshInfo();
    $this->assertTrue($theme_handler->themeExists('classy'));
  }

}
