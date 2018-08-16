<?php

namespace Drupal\block\Tests;

@trigger_error(__NAMESPACE__ . '\BlockTestBase is deprecated in Drupal 8.5.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\block\Functional\BlockTestBase, see https://www.drupal.org/node/2901823.', E_USER_DEPRECATED);

use Drupal\simpletest\WebTestBase;
use Drupal\filter\Entity\FilterFormat;

/**
 * Provides setup and helper methods for block module tests.
 *
 * @deprecated in Drupal 8.5.0 and will be removed before Drupal 9.0.0.
 *   Use \Drupal\Tests\block\Functional\BlockTestBase.
 *
 * @see https://www.drupal.org/node/2901823
 */
abstract class BlockTestBase extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'filter', 'test_page_test', 'help', 'block_test'];

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
    $this->config('system.site')->set('page.front', '/test-page')->save();

    // Create Full HTML text format.
    $full_html_format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
    ]);
    $full_html_format->save();

    // Create and log in an administrative user having access to the Full HTML
    // text format.
    $this->adminUser = $this->drupalCreateUser([
      'administer blocks',
      $full_html_format->getPermissionName(),
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);

    // Define the existing regions.
    $this->regions = [
      'header',
      'sidebar_first',
      'content',
      'sidebar_second',
      'footer',
    ];
    $block_storage = $this->container->get('entity_type.manager')->getStorage('block');
    $blocks = $block_storage->loadByProperties(['theme' => $this->config('system.theme')->get('default')]);
    foreach ($blocks as $block) {
      $block->delete();
    }
  }

}
