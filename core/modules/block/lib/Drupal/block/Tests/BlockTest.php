<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

class BlockTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'custom_block', 'test_page_test');

  protected $regions;
  protected $adminUser;

  public static function getInfo() {
    return array(
      'name' => 'Block functionality',
      'description' => 'Custom block functionality.',
      'group' => 'Block',
    );
  }

  function setUp() {
    parent::setUp();

    // Use the test page as the front page.
    config('system.site')->set('page.front', 'test-page')->save();

    // Create Full HTML text format.
    $full_html_format = array(
      'format' => 'full_html',
      'name' => 'Full HTML',
    );
    $full_html_format = (object) $full_html_format;
    filter_format_save($full_html_format);
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
    $this->regions = array();
    $this->regions[] = 'header';
    $this->regions[] = 'sidebar_first';
    $this->regions[] = 'content';
    $this->regions[] = 'sidebar_second';
    $this->regions[] = 'footer';
  }

  /**
   * Removes default blocks to avoid conflicts in the Block UI.
   */
  protected function removeDefaultBlocks() {
    $default_theme = variable_get('theme_default', 'stark');
    $manager = $this->container->get('plugin.manager.block');
    $instances = config_get_storage_names_with_prefix('plugin.core.block.' . $default_theme);
    foreach ($instances as $plugin_id) {
      config($plugin_id)->delete();
    }
  }

  /**
   * Test creating custom block, moving it to a specific region and then deleting it.
   */
  public function testCustomBlock() {
    $default_theme = variable_get('theme_default', 'stark');
    $this->removeDefaultBlocks();

    // Clear the block cache to load the Custom Block module's block definitions.
    $manager = $this->container->get('plugin.manager.block');
    $manager->clearCachedDefinitions();

    // Enable a second theme.
    theme_enable(array('seven'));

    // Confirm that the add block link appears on block overview pages.
    $this->drupalGet("admin/structure/block/list/block_plugin_ui:$default_theme/add");
    $this->assertLink(t('Add custom block'));

    // Confirm that hidden regions are not shown as options for block placement
    // when adding a new block.
    theme_enable(array('bartik'));
    $themes = list_themes();
    $this->drupalGet('admin/structure/block/add');
    foreach ($themes as $key => $theme) {
      if ($theme->status) {
        foreach ($theme->info['regions_hidden'] as $hidden_region) {
          $elements = $this->xpath('//select[@id=:id]//option[@value=:value]', array(':id' => 'edit-regions-' . $key, ':value' => $hidden_region));
          $this->assertFalse(isset($elements[0]), format_string('The hidden region @region is not available for @theme.', array('@region' => $hidden_region, '@theme' => $key)));
        }
      }
    }

    // Add a new custom block by filling out the input form on the admin/structure/block/add page.
    $info = strtolower($this->randomName(8));
    $custom_block['machine_name'] = $info;
    $custom_block['info'] = $info;
    $custom_block['title'] = $this->randomName(8);
    $custom_block['body[value]'] = $this->randomName(32);
    $custom_block['region'] = $this->regions[0];
    $this->drupalPost("admin/structure/block/list/block_plugin_ui:$default_theme/add/custom_blocks", $custom_block, t('Save block'));
    $plugin_id = "plugin.core.block.$default_theme.$info";
    $block = $manager->getInstance(array('config' => $plugin_id));
    $config = $block->getConfig();

    // Confirm that the custom block has been created, and then query the created bid.
    $this->assertText(t('The block configuration has been saved.'), 'Custom block successfully created.');

    // Check that block_block_view() returns the correct title and content.
    $data = $block->build();
    $format = $config['format'];
    $this->assertEqual(check_markup($custom_block['body[value]'], $format), render($data), 'BlockInterface::build() provides correct block content.');

    // Check whether the block can be moved to all available regions.
    $custom_block['module'] = 'block';
    foreach ($this->regions as $region) {
      $this->moveBlockToRegion($custom_block, $region);
    }

    // Verify presence of configure and delete links for custom block.
    $this->drupalGet('admin/structure/block');
    $config_block_id = "admin/structure/block/manage/plugin.core.block.$default_theme.$info/$default_theme";
    $this->assertLinkByHref("$config_block_id/configure", 0, 'Custom block configure link found.');
    $this->assertLinkByHref("$config_block_id/delete", 0, 'Custom block delete link found.');

    // Set visibility only for authenticated users, to verify delete functionality.
    $edit = array();
    $edit['visibility[role][roles][' . DRUPAL_AUTHENTICATED_RID . ']'] = TRUE;
    $this->drupalPost("$config_block_id/configure", $edit, t('Save block'));

    // Delete the created custom block & verify that it's been deleted and no longer appearing on the page.
    $this->clickLink(t('delete'));
    $this->drupalPost("$config_block_id/delete", array(), t('Delete'));
    $this->assertRaw(t('The block %title has been removed.', array('%title' => $custom_block['title'])), 'Custom block successfully deleted.');
    $this->drupalGet(NULL);
    $this->assertNoText(t($custom_block['title']), 'Custom block no longer appears on page.');
  }

  /**
   * Test creating custom block using Full HTML.
   */
  public function testCustomBlockFormat() {
    $default_theme = variable_get('theme_default', 'stark');
    $this->removeDefaultBlocks();

    // Add a new custom block by filling out the input form on the admin/structure/block/add page.
    $info = $this->randomName(8);
    $custom_block['machine_name'] = $info;
    $custom_block['info'] = $info;
    $custom_block['title'] = $this->randomName(8);
    $custom_block['body[value]'] = '<h1>Full HTML</h1>';
    $full_html_format = filter_format_load('full_html');
    $custom_block['body[format]'] = $full_html_format->format;
    $custom_block['region'] = $this->regions[0];
    $this->drupalPost("admin/structure/block/list/block_plugin_ui:$default_theme/add/custom_blocks", $custom_block, t('Save block'));

    // Set the created custom block to a specific region.
    $edit['blocks[0][region]'] = $this->regions[1];
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));

    // Confirm that the custom block is being displayed using configured text format.
    $this->drupalGet('');
    $this->assertRaw('<h1>Full HTML</h1>', 'Custom block successfully being displayed using Full HTML.');

    // Confirm that a user without access to Full HTML can not see the body field,
    // but can still submit the form without errors.
    $block_admin = $this->drupalCreateUser(array('administer blocks'));
    $config_block_id = "admin/structure/block/manage/plugin.core.block.$default_theme.$info/$default_theme";
    $this->drupalLogin($block_admin);
    $this->drupalGet("$config_block_id/configure");
    $this->assertFieldByXPath("//textarea[@name='body[value]' and @disabled='disabled']", t('This field has been disabled because you do not have sufficient permissions to edit it.'), 'Body field contains denied message');
    $this->drupalPost("$config_block_id/configure", array(), t('Save block'));
    $this->assertNoText(t('Ensure that each block description is unique.'));

    // Confirm that the custom block is still being displayed using configured text format.
    $this->drupalGet('');
    $this->assertRaw('<h1>Full HTML</h1>', 'Custom block successfully being displayed using Full HTML.');
  }

  /**
   * Test block visibility.
   */
  function testBlockVisibility() {
    $block_name = 'system_powered_by_block';
    // Create a random title for the block.
    $title = $this->randomName(8);
    // Enable a standard block.
    $default_theme = variable_get('theme_default', 'stark');
    $edit = array(
      'machine_name' => $this->randomName(8),
      'region' => 'sidebar_first',
      'title' => $title,
    );
    // Set the block to be hidden on any user path, and to be shown only to
    // authenticated users.
    $edit['visibility[path][pages]'] = 'user*';
    $edit['visibility[role][roles][' . DRUPAL_AUTHENTICATED_RID . ']'] = TRUE;
    $this->drupalPost('admin/structure/block/manage/' . $block_name . '/' . $default_theme, $edit, t('Save block'));
    $this->assertText('The block configuration has been saved.', 'Block was saved');

    $this->drupalGet('');
    $this->assertText($title, 'Block was displayed on the front page.');

    $this->drupalGet('user');
    $this->assertNoText($title, 'Block was not displayed according to block visibility rules.');

    $this->drupalGet('USER/' . $this->adminUser->uid);
    $this->assertNoText($title, 'Block was not displayed according to block visibility rules regardless of path case.');

    // Confirm that the block is not displayed to anonymous users.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoText($title, 'Block was not displayed to anonymous users.');

    // Confirm that an empty block is not displayed.
    $this->assertNoText('Powered by Drupal', 'Empty block not displayed.');
  }

  /**
   * Test block visibility when using "pages" restriction but leaving
   * "pages" textarea empty
   */
  function testBlockVisibilityListedEmpty() {
    $block_name = 'system_powered_by_block';
    // Create a random title for the block.
    $title = $this->randomName(8);
    // Enable a standard block.
    $default_theme = variable_get('theme_default', 'stark');
    $edit = array(
      'machine_name' => $this->randomName(8),
      'region' => 'sidebar_first',
      'title' => $title,
      'visibility[path][visibility]' => BLOCK_VISIBILITY_LISTED,
    );
    // Set the block to be hidden on any user path, and to be shown only to
    // authenticated users.
    $this->drupalPost('admin/structure/block/manage/' . $block_name . '/' . $default_theme, $edit, t('Save block'));
    $this->assertText('The block configuration has been saved.', 'Block was saved');

    $this->drupalGet('user');
    $this->assertNoText($title, 'Block was not displayed according to block visibility rules.');

    $this->drupalGet('USER');
    $this->assertNoText($title, 'Block was not displayed according to block visibility rules regardless of path case.');

    // Confirm that the block is not displayed to anonymous users.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoText($title, 'Block was not displayed to anonymous users on the front page.');
  }

  /**
   * Test configuring and moving a module-define block to specific regions.
   */
  function testBlock() {
    $this->removeDefaultBlocks();
    // Select the 'Powered by Drupal' block to be configured and moved.
    $block = array();
    $block['id'] = 'system_powered_by_block';
    $block['title'] = $this->randomName(8);
    $block['machine_name'] = $this->randomName(8);
    $block['theme'] = variable_get('theme_default', 'stark');
    $block['region'] = 'header';

    // Set block title to confirm that interface works and override any custom titles.
    $this->drupalPost('admin/structure/block/manage/' . $block['id'] . '/' . $block['theme'], array('title' => $block['title'], 'machine_name' => $block['machine_name'], 'region' => $block['region']), t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), 'Block title set.');
    // Check to see if the block was created by checking its configuration.
    $block['config_id'] = 'plugin.core.block.' . $block['theme'] . '.' . $block['machine_name'];
    $instance = block_load($block['config_id']);
    $config = $instance->getConfig();

    $this->assertEqual($config['subject'], $block['title'], 'Stored block title found.');

    // Check whether the block can be moved to all available regions.
    foreach ($this->regions as $region) {
      $this->moveBlockToRegion($block, $region);
    }

    // Set the block to the disabled region.
    $edit = array();
    $edit['blocks[0][region]'] = -1;
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));

    // Confirm that the block is now listed as disabled.
    $this->assertText(t('The block settings have been updated.'), 'Block successfully move to disabled region.');

    // Confirm that the block instance title and markup are not displayed.
    $this->drupalGet('node');
    $this->assertNoText(t($block['title']));
    // Check for <div id="block-my-block-instance-name"> if the machine name
    // is my_block_instance_name.
    $xpath = $this->buildXPathQuery('//div[@id=:id]/*', array(':id' => 'block-' . strtr(strtolower($block['machine_name']), '-', '_')));
    $this->assertNoFieldByXPath($xpath, FALSE, 'Block found in no regions.');
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

  /**
   * Test _block_rehash().
   */
  function testBlockRehash() {
    module_enable(array('block_test'));
    $this->assertTrue(module_exists('block_test'), 'Test block module enabled.');

    // Clear the block cache to load the block_test module's block definitions.
    $manager = $this->container->get('plugin.manager.block');
    $manager->clearCachedDefinitions();

    // Add a test block.
    $plugin = $manager->getDefinition('test_cache');
    $block = array();
    $block['id'] = 'test_cache';
    $block['machine_name'] = $this->randomName(8);
    $block['theme'] = variable_get('theme_default', 'stark');
    $block['region'] = 'header';
    $this->drupalPost('admin/structure/block/manage/' . $block['id'] . '/' . $block['theme'], array('machine_name' => $block['machine_name'], 'region' => $block['region']), t('Save block'));

    // Our test block's caching should default to DRUPAL_CACHE_PER_ROLE.
    $block['config_id'] = 'plugin.core.block.' . $block['theme'] . '.' . $block['machine_name'];
    $instance = block_load($block['config_id']);
    $config = $instance->getConfig();
    $this->assertEqual($config['cache'], DRUPAL_CACHE_PER_ROLE, 'Test block cache mode defaults to DRUPAL_CACHE_PER_ROLE.');

    // Disable caching for this block.
    $block_config = config($block['config_id']);
    $block_config->set('cache', DRUPAL_NO_CACHE);
    $block_config->save();
    // Flushing all caches should call _block_rehash().
    $this->resetAll();
    // Verify that block is updated with the new caching mode.
    $instance = block_load($block['config_id']);
    $config = $instance->getConfig();
    $this->assertEqual($config['cache'], DRUPAL_NO_CACHE, "Test block's database entry updated to DRUPAL_NO_CACHE.");
  }

  /**
   * Tests blocks belonging to disabled modules.
   */
  function testBlockModuleDisable() {
    module_enable(array('block_test'));
    $this->assertTrue(module_exists('block_test'), 'Test block module enabled.');

    // Clear the block cache to load the block_test module's block definitions.
    $manager = $this->container->get('plugin.manager.block');
    $manager->clearCachedDefinitions();

    // Add test blocks in different regions and confirm they are displayed.
    $blocks = array();
    $regions = array('sidebar_first', 'content', 'footer');
    foreach ($regions as $region) {
      $blocks[$region] = $this->drupalPlaceBlock('test_cache', array('region' => $region));
    }
    $this->drupalGet('');
    foreach ($regions as $region) {
      $this->assertText($blocks[$region]['subject']);
    }

    // Disable the block test module and refresh the definitions cache.
    module_disable(array('block_test'), FALSE);
    $this->assertFalse(module_exists('block_test'), 'Test block module disabled.');
    $manager->clearCachedDefinitions();

    // Ensure that the block administration page still functions as expected.
    $this->drupalGet('admin/structure/block');
    $this->assertResponse(200);
    // A 200 response is possible with a fatal error, so check the title too.
    $this->assertTitle(t('Blocks | Drupal'));

    // Ensure that the disabled module's block instance is not listed.
    foreach ($regions as $region) {
      $this->assertNoText($blocks[$region]['subject']);
    }

    // Ensure that the disabled module's block plugin is no longer available.
    $this->drupalGet('admin/structure/block/list/block_plugin_ui:' . variable_get('theme_default', 'stark') . '/add');
    $this->assertNoText(t('Test block caching'));

    // Confirm that the block is no longer displayed on the front page.
    $this->drupalGet('');
    $this->assertResponse(200);
    foreach ($regions as $region) {
      $this->assertNoText($blocks[$region]['subject']);
    }

    // Confirm that a different block instance can still be enabled by
    // submitting the block library form.
    // Emulate a POST submission rather than using drupalPlaceBlock() to ensure
    // that the form still functions as expected.
    $edit = array(
      'title' => $this->randomName(8),
      'machine_name' => $this->randomName(8),
      'region' => 'sidebar_first',
    );
    $this->drupalPost('admin/structure/block/manage/system_powered_by_block/stark', $edit, t('Save block'));
    $this->assertText(t('The block configuration has been saved.'));
    $this->assertText($edit['title']);

    // Update the weight of a block.
    $edit = array('blocks[0][weight]' => -1);
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));
    $this->assertText(t('The block settings have been updated.'));

    // Re-enable the module and refresh the definitions cache.
    module_enable(array('block_test'), FALSE);
    $this->assertTrue(module_exists('block_test'), 'Test block module re-enabled.');
    $manager->clearCachedDefinitions();

    // Reload the admin page and confirm the block can again be configured.
    $this->drupalGet('admin/structure/block');
    foreach ($regions as $region) {
      $this->assertLinkByHref(url('admin/structure/block/manage/' . $blocks[$region]['config_id'] . '/stark/config'));
    }

    // Confirm that the blocks are again displayed on the front page in the
    // correct regions.
    $this->drupalGet('');
    foreach ($regions as $region) {
      // @todo Use a proper method for this.
      $name_pieces = explode('.', $blocks[$region]['config_id']);
      $machine_name = array_pop($name_pieces);
      $xpath = $this->buildXPathQuery('//div[@class=:region-class]//div[@id=:block-id]/*', array(
        ':region-class' => 'region region-' . drupal_html_class($region),
        ':block-id' => 'block-' . strtr(strtolower($machine_name), '-', '_'),
    ));
      $this->assertFieldByXPath($xpath, NULL, format_string('Block %name found in the %region region.', array(
        '%name' => $blocks[$region]['subject'],
        '%region' => $region,
      )));
    }
  }

}
