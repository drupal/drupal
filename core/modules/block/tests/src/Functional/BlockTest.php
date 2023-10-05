<?php

namespace Drupal\Tests\block\Functional;

use Drupal\Component\Utility\Html;
use Drupal\block\Entity\Block;
use Drupal\Core\Url;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests basic block functionality.
 *
 * @group block
 * @group #slow
 */
class BlockTest extends BlockTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests block visibility.
   */
  public function testBlockVisibility() {
    $block_name = 'system_powered_by_block';
    // Create a random title for the block.
    $title = $this->randomMachineName(8);
    // Enable a standard block.
    $default_theme = $this->config('system.theme')->get('default');
    $edit = [
      'id' => $this->randomMachineName(8),
      'region' => 'sidebar_first',
      'settings[label]' => $title,
      'settings[label_display]' => TRUE,
    ];
    // Set the block to be hidden on any user path, to be shown only to
    // authenticated users, and to be shown only on 200 and 404 responses.
    $edit['visibility[request_path][pages]'] = '/user*';
    $edit['visibility[request_path][negate]'] = TRUE;
    $edit['visibility[user_role][roles][' . RoleInterface::AUTHENTICATED_ID . ']'] = TRUE;
    $edit['visibility[response_status][status_codes][200]'] = 200;
    $edit['visibility[response_status][status_codes][404]'] = 404;
    $this->drupalGet('admin/structure/block/add/' . $block_name . '/' . $default_theme);
    $this->assertSession()->checkboxChecked('edit-visibility-request-path-negate-0');

    $this->submitForm($edit, 'Save block');
    $this->assertSession()->statusMessageContains('The block configuration has been saved.', 'status');

    $this->clickLink('Configure');
    $this->assertSession()->checkboxChecked('edit-visibility-request-path-negate-1');
    $this->assertSession()->checkboxChecked('edit-visibility-response-status-status-codes-200');
    $this->assertSession()->checkboxChecked('edit-visibility-response-status-status-codes-404');

    // Confirm that the block is displayed on the front page (200 response).
    $this->drupalGet('');
    $this->assertSession()->pageTextContains($title);

    // Confirm that the block is not displayed according to path visibility
    // rules.
    $this->drupalGet('user');
    $this->assertSession()->pageTextNotContains($title);

    // Confirm that the block is displayed on a 404 response.
    $this->drupalGet('/0/null');
    $this->assertSession()->pageTextContains($title);

    // Confirm that the block is not displayed on a 403 response.
    $this->drupalGet('/admin/config/system/cron');
    $this->assertSession()->pageTextNotContains($title);

    // Confirm that the block is not displayed to anonymous users.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertSession()->pageTextNotContains($title);

    // Confirm that an empty block is not displayed.
    $this->assertSession()->pageTextNotContains('Powered by Drupal');
    $this->assertSession()->responseNotContains('sidebar-first');
  }

  /**
   * Tests that visibility can be properly toggled.
   */
  public function testBlockToggleVisibility() {
    $block_name = 'system_powered_by_block';
    // Create a random title for the block.
    $title = $this->randomMachineName(8);
    // Enable a standard block.
    $default_theme = $this->config('system.theme')->get('default');
    $edit = [
      'id' => $this->randomMachineName(8),
      'region' => 'sidebar_first',
      'settings[label]' => $title,
    ];
    $block_id = $edit['id'];
    // Set the block to be shown only to authenticated users.
    $edit['visibility[user_role][roles][' . RoleInterface::AUTHENTICATED_ID . ']'] = TRUE;
    $this->drupalGet('admin/structure/block/add/' . $block_name . '/' . $default_theme);
    $this->submitForm($edit, 'Save block');
    $this->clickLink('Configure');
    $this->assertSession()->checkboxChecked('edit-visibility-user-role-roles-authenticated');

    $edit = [
      'visibility[user_role][roles][' . RoleInterface::AUTHENTICATED_ID . ']' => FALSE,
    ];
    $this->submitForm($edit, 'Save block');
    $this->clickLink('Configure');
    $this->assertSession()->checkboxNotChecked('edit-visibility-user-role-roles-authenticated');

    // Ensure that no visibility is configured.
    /** @var \Drupal\block\BlockInterface $block */
    $block = Block::load($block_id);
    $visibility_config = $block->getVisibilityConditions()->getConfiguration();
    $this->assertSame([], $visibility_config);
    $this->assertSame([], $block->get('visibility'));
  }

  /**
   * Tests block visibility when leaving "pages" textarea empty.
   */
  public function testBlockVisibilityListedEmpty() {
    $block_name = 'system_powered_by_block';
    // Create a random title for the block.
    $title = $this->randomMachineName(8);
    // Enable a standard block.
    $default_theme = $this->config('system.theme')->get('default');
    $edit = [
      'id' => $this->randomMachineName(8),
      'region' => 'sidebar_first',
      'settings[label]' => $title,
      'visibility[request_path][negate]' => TRUE,
    ];
    // Set the block to be hidden on any user path, and to be shown only to
    // authenticated users.
    $this->drupalGet('admin/structure/block/add/' . $block_name . '/' . $default_theme);
    $this->submitForm($edit, 'Save block');
    $this->assertSession()->statusMessageContains('The block configuration has been saved.', 'status');

    // Confirm that block was not displayed according to block visibility
    // rules.
    $this->drupalGet('user');
    $this->assertSession()->pageTextNotContains($title);

    // Confirm that block was not displayed according to block visibility
    // rules regardless of path case.
    $this->drupalGet('USER');
    $this->assertSession()->pageTextNotContains($title);

    // Confirm that the block is not displayed to anonymous users.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertSession()->pageTextNotContains($title);
  }

  /**
   * Tests adding a block from the library page with a weight query string.
   */
  public function testAddBlockFromLibraryWithWeight() {
    $default_theme = $this->config('system.theme')->get('default');
    // Test one positive, zero, and one negative weight.
    foreach (['7', '0', '-9'] as $weight) {
      $options = [
        'query' => [
          'region' => 'sidebar_first',
          'weight' => $weight,
        ],
      ];
      $this->drupalGet(Url::fromRoute('block.admin_library', ['theme' => $default_theme], $options));

      $block_name = 'system_powered_by_block';
      $add_url = Url::fromRoute('block.admin_add', [
        'plugin_id' => $block_name,
        'theme' => $default_theme,
      ]);

      // Verify that one link is found, with the expected link text.
      $xpath = $this->assertSession()->buildXPathQuery('//a[contains(@href, :href)]', [':href' => $add_url->toString()]);
      $this->assertSession()->elementsCount('xpath', $xpath, 1);
      $this->assertSession()->elementTextEquals('xpath', $xpath, 'Place block');

      $link = $this->getSession()->getPage()->find('xpath', $xpath);
      [$path, $query_string] = explode('?', $link->getAttribute('href'), 2);
      parse_str($query_string, $query_parts);
      $this->assertEquals($weight, $query_parts['weight'], 'Found the expected weight query string.');

      // Create a random title for the block.
      $title = $this->randomMachineName(8);
      $block_id = $this->randomMachineName(8);
      $edit = [
        'id' => $block_id,
        'settings[label]' => $title,
      ];
      // Create the block using the link parsed from the library page.
      $this->drupalGet($this->getAbsoluteUrl($link->getAttribute('href')));
      $this->submitForm($edit, 'Save block');

      // Ensure that the block was created with the expected weight.
      /** @var \Drupal\block\BlockInterface $block */
      $block = Block::load($block_id);
      $this->assertEquals($weight, $block->getWeight(), 'Found the block with expected weight.');
    }
  }

  /**
   * Tests configuring and moving a module-define block to specific regions.
   */
  public function testBlock() {
    // Place page title block to test error messages.
    $this->drupalPlaceBlock('page_title_block');

    // Disable the block.
    $this->drupalGet('admin/structure/block');
    $this->clickLink('Disable');

    // Select the 'Powered by Drupal' block to be configured and moved.
    $block = [];
    $block['id'] = 'system_powered_by_block';
    $block['settings[label]'] = $this->randomMachineName(8);
    $block['settings[label_display]'] = TRUE;
    $block['theme'] = $this->config('system.theme')->get('default');
    $block['region'] = 'header';

    // Set block title to confirm that interface works and override any custom titles.
    $this->drupalGet('admin/structure/block/add/' . $block['id'] . '/' . $block['theme']);
    $this->submitForm([
      'settings[label]' => $block['settings[label]'],
      'settings[label_display]' => $block['settings[label_display]'],
      'id' => $block['id'],
      'region' => $block['region'],
    ], 'Save block');
    $this->assertSession()->statusMessageContains('The block configuration has been saved.', 'status');
    // Check to see if the block was created by checking its configuration.
    $instance = Block::load($block['id']);

    $this->assertEquals($block['settings[label]'], $instance->label(), 'Stored block title found.');

    // Check whether the block can be moved to all available regions.
    foreach ($this->regions as $region) {
      $this->moveBlockToRegion($block, $region);
    }

    // Disable the block.
    $this->drupalGet('admin/structure/block');
    $this->clickLink('Disable');

    // Confirm that the block is now listed as disabled.
    $this->assertSession()->statusMessageContains('The block settings have been updated.', 'status');

    // Confirm that the block instance title and markup are not displayed.
    $this->drupalGet('node');
    $this->assertSession()->pageTextNotContains($block['settings[label]']);
    // Check for <div id="block-my-block-instance-name"> if the machine name
    // is my_block_instance_name.
    $xpath = $this->assertSession()->buildXPathQuery('//div[@id=:id]/*', [':id' => 'block-' . str_replace('_', '-', strtolower($block['id']))]);
    $this->assertSession()->elementNotExists('xpath', $xpath);

    $pages = [
      '',
      '<front>',
      '/valid-page',
      'user/login',
    ];
    // Test error when not including forward slash.
    $this->drupalGet('admin/structure/block/manage/' . $block['id']);
    $this->submitForm(['visibility[request_path][pages]' => implode("\n", $pages)], 'Save block');
    $this->assertSession()->pageTextContains('The path user/login requires a leading forward slash when used with the Pages setting.');

    // Test deleting the block from the edit form.
    $this->drupalGet('admin/structure/block/manage/' . $block['id']);
    $this->clickLink('Remove block');
    $this->assertSession()->pageTextContains('Are you sure you want to remove the block ' . $block['settings[label]'] . ' from the Footer region?');
    $this->submitForm([], 'Remove');
    $this->assertSession()->statusMessageContains('The block ' . $block['settings[label]'] . ' has been removed from the Footer region.', 'status');

    // Test deleting a block via "Configure block" link.
    $block = $this->drupalPlaceBlock('system_powered_by_block', [
      'region' => 'left_sidebar',
    ]);
    $this->drupalGet('admin/structure/block/manage/' . $block->id(), ['query' => ['destination' => 'admin']]);
    $this->clickLink('Remove block');
    $this->assertSession()->pageTextContains('Are you sure you want to remove the block ' . $block->label() . ' from the Left sidebar region?');
    $this->submitForm([], 'Remove');
    $this->assertSession()->statusMessageContains('The block ' . $block->label() . ' has been removed from the Left sidebar region.', 'status');
    $this->assertSession()->addressEquals('admin');
    $this->assertSession()->responseNotContains($block->id());
  }

  /**
   * Tests that the block form has a theme selector when not passed via the URL.
   */
  public function testBlockThemeSelector() {
    // Install all themes.
    $themes = [
      'olivero',
      'claro',
      'stark',
    ];
    \Drupal::service('theme_installer')->install($themes);
    $theme_settings = $this->config('system.theme');
    foreach ($themes as $theme) {
      $this->drupalGet('admin/structure/block/list/' . $theme);
      $this->assertSession()->titleEquals('Block layout | Drupal');
      // Select the 'Powered by Drupal' block to be placed.
      $block = [];
      $block['id'] = $this->randomMachineName();
      $block['theme'] = $theme;
      $block['region'] = 'content';
      $this->drupalGet('admin/structure/block/add/system_powered_by_block');
      $this->submitForm($block, 'Save block');
      $this->assertSession()->statusMessageContains('The block configuration has been saved.', 'status');
      $this->assertSession()->addressEquals('admin/structure/block/list/' . $theme . '?block-placement=' . Html::getClass($block['id']));

      // Set the default theme and ensure the block is placed.
      $theme_settings->set('default', $theme)->save();
      $this->drupalGet('');
      $block_id = Html::getUniqueId('block-' . $block['id']);
      $this->assertSession()->elementExists('xpath', "//div[@id = '$block_id']");
    }
  }

  /**
   * Tests block display of theme titles.
   */
  public function testThemeName() {
    // Enable the help block.
    $this->drupalPlaceBlock('help_block', ['region' => 'help']);
    $this->drupalPlaceBlock('local_tasks_block');
    // Explicitly set the default and admin themes.
    $theme = 'block_test_specialchars_theme';
    \Drupal::service('theme_installer')->install([$theme]);
    $this->drupalGet('admin/structure/block');
    $this->assertSession()->assertEscaped('<"Cat" & \'Mouse\'>');
    $this->drupalGet('admin/structure/block/list/block_test_specialchars_theme');
    $this->assertSession()->assertEscaped('Demonstrate block regions (<"Cat" & \'Mouse\'>)');
  }

  /**
   * Tests block title display settings.
   */
  public function testHideBlockTitle() {
    $block_name = 'system_powered_by_block';
    // Create a random title for the block.
    $title = $this->randomMachineName(8);
    $id = $this->randomMachineName(8);
    // Enable a standard block.
    $default_theme = $this->config('system.theme')->get('default');
    $edit = [
      'id' => $id,
      'region' => 'sidebar_first',
      'settings[label]' => $title,
    ];
    $this->drupalGet('admin/structure/block/add/' . $block_name . '/' . $default_theme);
    $this->submitForm($edit, 'Save block');
    $this->assertSession()->statusMessageContains('The block configuration has been saved.', 'status');

    // Confirm that the block is not displayed by default.
    $this->drupalGet('user');
    $this->assertSession()->pageTextNotContains($title);

    $edit = [
      'settings[label_display]' => TRUE,
    ];
    $this->drupalGet('admin/structure/block/manage/' . $id);
    $this->submitForm($edit, 'Save block');
    $this->assertSession()->statusMessageContains('The block configuration has been saved.', 'status');

    $this->drupalGet('admin/structure/block/manage/' . $id);
    $this->assertSession()->checkboxChecked('edit-settings-label-display');

    // Confirm that the block is displayed when enabled.
    $this->drupalGet('user');
    $this->assertSession()->pageTextContains($title);
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
  public function moveBlockToRegion(array $block, $region) {
    // Set the created block to a specific region.
    $block += ['theme' => $this->config('system.theme')->get('default')];
    $edit = [];
    $edit['blocks[' . $block['id'] . '][region]'] = $region;
    $this->drupalGet('admin/structure/block');
    $this->submitForm($edit, 'Save blocks');

    // Confirm that the block was moved to the proper region.
    $this->assertSession()->statusMessageContains('The block settings have been updated.', 'status');

    // Confirm that the block is being displayed.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains($block['settings[label]']);

    $region_xpath = [
      'header' => '//header[@role = "banner"]',
      'sidebar_first' => '//aside[contains(@class, "layout-sidebar-first")]',
      'content' => '//div[contains(@class, "layout-content")]',
      'sidebar_second' => '//aside[contains(@class, "layout-sidebar-second")]',
      'footer' => '//footer[@role = "contentinfo"]',
    ];

    // Confirm that the content block was found at the proper region.
    $xpath = $this->assertSession()->buildXPathQuery($region_xpath[$region] . '//div[@id=:block-id]/*', [
      ':block-id' => 'block-' . str_replace('_', '-', strtolower($block['id'])),
    ]);
    $this->assertSession()->elementExists('xpath', $xpath);
  }

  /**
   * Tests that cache tags are properly set and bubbled up to the page cache.
   *
   * Verify that invalidation of these cache tags works:
   * - "block:<block ID>"
   * - "block_plugin:<block plugin ID>"
   */
  public function testBlockCacheTags() {
    // The page cache only works for anonymous users.
    $this->drupalLogout();

    // Enable page caching.
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();

    // Place the "Powered by Drupal" block.
    $block = $this->drupalPlaceBlock('system_powered_by_block', ['id' => 'powered']);

    // Prime the page cache.
    $this->drupalGet('<front>');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');

    // Verify a cache hit, but also the presence of the correct cache tags in
    // both the page and block caches.
    $this->drupalGet('<front>');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $cid_parts = [Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(), ''];
    $cid = implode(':', $cid_parts);
    $cache_entry = \Drupal::cache('page')->get($cid);
    $expected_cache_tags = [
      'config:block_list',
      'block_view',
      'config:block.block.powered',
      'config:user.role.anonymous',
      'http_response',
      'rendered',
    ];
    sort($expected_cache_tags);
    $keys = \Drupal::service('cache_contexts_manager')->convertTokensToKeys(['languages:language_interface', 'theme', 'user.permissions'])->getKeys();
    $this->assertSame($expected_cache_tags, $cache_entry->tags);
    $cache_entry = \Drupal::cache('render')->get('entity_view:block:powered:' . implode(':', $keys));
    $expected_cache_tags = [
      'block_view',
      'config:block.block.powered',
      'rendered',
    ];
    sort($expected_cache_tags);
    $this->assertSame($expected_cache_tags, $cache_entry->tags);

    // The "Powered by Drupal" block is modified; verify a cache miss.
    $block->setRegion('content');
    $block->save();
    $this->drupalGet('<front>');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');

    // Now we should have a cache hit again.
    $this->drupalGet('<front>');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');

    // Place the "Powered by Drupal" block another time; verify a cache miss.
    $this->drupalPlaceBlock('system_powered_by_block', ['id' => 'powered_2']);
    $this->drupalGet('<front>');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');

    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->drupalGet('<front>');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $cid_parts = [Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(), ''];
    $cid = implode(':', $cid_parts);
    $cache_entry = \Drupal::cache('page')->get($cid);
    $expected_cache_tags = [
      'config:block_list',
      'block_view',
      'config:block.block.powered',
      'config:block.block.powered_2',
      'config:user.role.anonymous',
      'http_response',
      'rendered',
    ];
    sort($expected_cache_tags);
    $this->assertEquals($expected_cache_tags, $cache_entry->tags);
    $expected_cache_tags = [
      'block_view',
      'config:block.block.powered',
      'rendered',
    ];
    sort($expected_cache_tags);
    $keys = \Drupal::service('cache_contexts_manager')->convertTokensToKeys(['languages:language_interface', 'theme', 'user.permissions'])->getKeys();
    $cache_entry = \Drupal::cache('render')->get('entity_view:block:powered:' . implode(':', $keys));
    $this->assertSame($expected_cache_tags, $cache_entry->tags);
    $expected_cache_tags = [
      'block_view',
      'config:block.block.powered_2',
      'rendered',
    ];
    sort($expected_cache_tags);
    $keys = \Drupal::service('cache_contexts_manager')->convertTokensToKeys(['languages:language_interface', 'theme', 'user.permissions'])->getKeys();
    $cache_entry = \Drupal::cache('render')->get('entity_view:block:powered_2:' . implode(':', $keys));
    $this->assertSame($expected_cache_tags, $cache_entry->tags);

    // Now we should have a cache hit again.
    $this->drupalGet('<front>');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');

    // Delete the "Powered by Drupal" blocks; verify a cache miss.
    $block_storage = \Drupal::entityTypeManager()->getStorage('block');
    $block_storage->load('powered')->delete();
    $block_storage->load('powered_2')->delete();
    $this->drupalGet('<front>');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
  }

  /**
   * Tests that a link exists to block layout from the appearance form.
   */
  public function testThemeAdminLink() {
    $this->drupalPlaceBlock('help_block', ['region' => 'help']);
    $theme_admin = $this->drupalCreateUser([
      'administer blocks',
      'administer themes',
      'access administration pages',
    ]);
    $this->drupalLogin($theme_admin);
    $this->drupalGet('admin/appearance');
    $this->assertSession()->pageTextContains('You can place blocks for each theme on the block layout page');
    $this->assertSession()->linkByHrefExists('admin/structure/block');
  }

  /**
   * Tests that uninstalling a theme removes its block configuration.
   */
  public function testUninstallTheme() {
    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = \Drupal::service('theme_installer');

    $theme_installer->install(['claro']);
    $this->config('system.theme')->set('default', 'claro')->save();
    $block = $this->drupalPlaceBlock('system_powered_by_block', ['theme' => 'claro', 'region' => 'help']);
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('Powered by Drupal');

    $this->config('system.theme')->set('default', 'stark')->save();
    $theme_installer->uninstall(['claro']);

    // Ensure that the block configuration does not exist anymore.
    $this->assertNull(Block::load($block->id()));
  }

  /**
   * Tests the block access.
   */
  public function testBlockAccess() {
    $this->drupalPlaceBlock('test_access', ['region' => 'help']);

    $this->drupalGet('<front>');
    $this->assertSession()->pageTextNotContains('Hello test world');

    \Drupal::state()->set('test_block_access', TRUE);
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('Hello test world');
  }

  /**
   * Tests block_user_role_delete.
   */
  public function testBlockUserRoleDelete() {
    $role1 = Role::create(['id' => 'test_role1', 'label' => 'Test role 1']);
    $role1->save();

    $role2 = Role::create(['id' => 'test_role2', 'label' => 'Test role 2']);
    $role2->save();

    $block = Block::create([
      'id' => $this->randomMachineName(),
      'plugin' => 'system_powered_by_block',
      'theme' => 'stark',
    ]);

    $block->setVisibilityConfig('user_role', [
      'roles' => [
        $role1->id() => $role1->id(),
        $role2->id() => $role2->id(),
      ],
    ]);

    $block->save();

    $this->assertEquals([$role1->id() => $role1->id(), $role2->id() => $role2->id()], $block->getVisibility()['user_role']['roles']);

    $role1->delete();

    $block = Block::load($block->id());
    $this->assertEquals([$role2->id() => $role2->id()], $block->getVisibility()['user_role']['roles']);
  }

  /**
   * Tests block title.
   */
  public function testBlockTitle() {
    // Create a custom title for the block.
    $title = "This block's <b>great!</b>";
    // Enable a standard block.
    $default_theme = $this->config('system.theme')->get('default');
    $edit = [
      'id' => 'test',
      'region' => 'sidebar_first',
      'settings[label]' => $title,
      'settings[label_display]' => TRUE,
    ];
    // Set the block to be shown only to authenticated users.
    $edit['visibility[user_role][roles][' . RoleInterface::AUTHENTICATED_ID . ']'] = TRUE;
    $this->drupalGet('admin/structure/block/add/foo/' . $default_theme);
    $this->submitForm($edit, 'Save block');

    // Ensure that the title is displayed as plain text.
    $elements = $this->xpath('//table/tbody/tr//td[contains(@class, "block")]');
    $this->assertEquals($title, $elements[0]->getText());

    $this->clickLink('Disable');
    $elements = $this->xpath('//table/tbody/tr//td[contains(@class, "block")]');
    $this->assertEquals("$title (disabled)", $elements[0]->getText());
  }

}
