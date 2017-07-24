<?php

namespace Drupal\Tests\media\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests media Install / Uninstall logic.
 *
 * @group media
 */
class MediaInstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['media'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['administer modules']));
  }

  /**
   * Tests reinstalling after being uninstalled.
   */
  public function testReinstallAfterUninstall() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Uninstall the media module.
    $this->container->get('module_installer')->uninstall(['media'], FALSE);

    // Install the media module again, through a test module that depends on it.
    // Note: We use a test module because in 8.4 the media module is hidden.
    // @todo Simplify this in https://www.drupal.org/node/2897028 once it's
    //   shown again.
    $this->drupalGet('/admin/modules');
    $page->checkField('modules[media_test_views][enable]');
    $page->pressButton('Install');
    $assert_session->pageTextContains('Some required modules must be enabled');
    $page->pressButton('Continue');
    $this->assertSession()->pageTextNotContains('could not be moved/copied because a file by that name already exists in the destination directory');
  }

}
