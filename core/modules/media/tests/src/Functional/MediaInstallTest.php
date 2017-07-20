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
    $this->container->get('module_installer')->uninstall(['media'], FALSE);
    $this->drupalGet('/admin/modules');
    $page->checkField('modules[media][enable]');
    $page->pressButton('Install');
    // @todo Remove this if-statement in https://www.drupal.org/node/2895059
    if ($page->find('css', 'h1')->getText() == 'Are you sure you wish to enable experimental modules?') {
      $page->pressButton('Continue');
    }
    $this->assertSession()->pageTextNotContains('could not be moved/copied because a file by that name already exists in the destination directory');
  }

}
