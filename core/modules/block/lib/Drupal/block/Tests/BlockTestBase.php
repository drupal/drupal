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
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'test_page_test');

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

  function setUp() {
    parent::setUp();

    // Use the test page as the front page.
    config('system.site')->set('page.front', 'test-page')->save();

    // Create Full HTML text format.
    $full_html_format = entity_create('filter_format', array(
      'format' => 'full_html',
      'name' => 'Full HTML',
    ));
    $full_html_format->save();
    $this->checkPermissions(array(), TRUE);

    // Create and log in an administrative user having access to the Full HTML
    // text format.
    $this->adminUser = $this->drupalCreateUser(array(
      'administer blocks',
      filter_permission_name($full_html_format),
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

    $default_theme = variable_get('theme_default', 'stark');
    $manager = $this->container->get('plugin.manager.block');
    $instances = config_get_storage_names_with_prefix('plugin.core.block.' . $default_theme);
    foreach ($instances as $plugin_id) {
      config($plugin_id)->delete();
    }
  }

  /**
   * Moves a block to a given region via the UI and confirms the result.
   *
   * @param array $block
   *   An array of information about the block, including the following keys:
   *   - module: The module providing the block.
   *   - title: The title of the block.
   *   - delta: The block's delta key.
   * @param string $region
   *   The machine name of the theme region to move the block to, for example
   *   'header' or 'sidebar_first'.
   */
  function moveBlockToRegion(array $block, $region) {
    // Set the created block to a specific region.
    $edit = array();
    $edit['blocks[0][region]'] = $region;
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));

    // Confirm that the block was moved to the proper region.
    $this->assertText(t('The block settings have been updated.'), format_string('Block successfully moved to %region_name region.', array( '%region_name' => $region)));

    // Confirm that the block is being displayed.
    $this->drupalGet('');
    $this->assertText(t($block['title']), 'Block successfully being displayed on the page.');

    // Confirm that the custom block was found at the proper region.
    $xpath = $this->buildXPathQuery('//div[@class=:region-class]//div[@id=:block-id]/*', array(
     ':region-class' => 'region region-' . drupal_html_class($region),
     ':block-id' => 'block-' . strtr(strtolower($block['machine_name']), '-', '_'),
    ));
    $this->assertFieldByXPath($xpath, NULL, t('Block found in %region_name region.', array('%region_name' => drupal_html_class($region))));
  }

}
