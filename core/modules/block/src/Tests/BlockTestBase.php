<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockTestBase.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides setup and helper methods for block module tests.
 */
abstract class BlockTestBase extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('block', 'filter', 'test_page_test', 'help', 'block_test');

  /**
   * A list of theme regions to test.
   *
   * @var array
   */
  protected $regions;

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    // Use the test page as the front page.
    $this->config('system.site')->set('page.front', 'test-page')->save();

    // Create Full HTML text format.
    $full_html_format = entity_create('filter_format', array(
      'format' => 'full_html',
      'name' => 'Full HTML',
    ));
    $full_html_format->save();

    // Create and log in an administrative user having access to the Full HTML
    // text format.
    $this->adminUser = $this->drupalCreateUser(array(
      'administer blocks',
      $full_html_format->getPermissionName(),
      'access administration pages',
    ));
    $this->drupalLogin($this->adminUser);

    // Define the existing regions.
    $this->regions = array(
      'header',
      'sidebar_first',
      'content',
      'sidebar_second',
      'footer',
    );
    $block_storage = $this->container->get('entity.manager')->getStorage('block');
    $blocks = $block_storage->loadByProperties(array('theme' => $this->config('system.theme')->get('default')));
    foreach ($blocks as $block) {
      $block->delete();
    }
  }
}
