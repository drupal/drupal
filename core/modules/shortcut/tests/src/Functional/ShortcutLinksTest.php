<?php

namespace Drupal\Tests\shortcut\Functional;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\shortcut\Entity\Shortcut;
use Drupal\shortcut\Entity\ShortcutSet;
use Drupal\Tests\block\Functional\AssertBlockAppearsTrait;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;
use Drupal\views\Entity\View;

/**
 * Create, view, edit, delete, and change shortcut links.
 *
 * @group shortcut
 */
class ShortcutLinksTest extends ShortcutTestBase {

  use AssertBlockAppearsTrait;
  use PathAliasTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['router_test', 'views', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests that creating a shortcut works properly.
   */
  public function testShortcutLinkAdd() {
    $set = $this->set;

    // Create an alias for the node so we can test aliases.
    $path_alias = $this->createPathAlias('/node/' . $this->node->id(), '/' . $this->randomMachineName(8));

    // Create some paths to test.
    $test_cases = [
      '/',
      '/admin',
      '/admin/config/system/site-information',
      '/node/' . $this->node->id() . '/edit',
      $path_alias->getAlias(),
      '/router_test/test2',
      '/router_test/test3/value',
    ];

    $test_cases_non_access = [
      '/admin',
      '/admin/config/system/site-information',
    ];

    // Test the add shortcut form UI. Test that the base field description is
    // there.
    $this->drupalGet('admin/config/user-interface/shortcut/manage/' . $set->id() . '/add-link');
    $this->assertRaw('The location this shortcut points to.');

    // Check that each new shortcut links where it should.
    foreach ($test_cases as $test_path) {
      $title = $this->randomMachineName();
      $form_data = [
        'title[0][value]' => $title,
        'link[0][uri]' => $test_path,
      ];
      $this->drupalPostForm('admin/config/user-interface/shortcut/manage/' . $set->id() . '/add-link', $form_data, 'Save');
      $this->assertSession()->statusCodeEquals(200);
      $this->assertText('Added a shortcut for ' . $title . '.');
      $saved_set = ShortcutSet::load($set->id());
      $paths = $this->getShortcutInformation($saved_set, 'link');
      $this->assertContains('internal:' . $test_path, $paths, 'Shortcut created: ' . $test_path);

      if (in_array($test_path, $test_cases_non_access)) {
        $this->assertSession()->linkNotExists($title, new FormattableMarkup('Shortcut link %url not accessible on the page.', ['%url' => $test_path]));
      }
      else {
        $this->assertSession()->linkExists($title, 0, new FormattableMarkup('Shortcut link %url found on the page.', ['%url' => $test_path]));
      }
    }
    $saved_set = ShortcutSet::load($set->id());
    // Test that saving and re-loading a shortcut preserves its values.
    $shortcuts = $saved_set->getShortcuts();
    foreach ($shortcuts as $entity) {
      // Test the node routes with parameters.
      $entity->save();
      $loaded = Shortcut::load($entity->id());
      $this->assertEqual($entity->link->uri, $loaded->link->uri);
      $this->assertEqual($entity->link->options, $loaded->link->options);
    }

    // Log in as non admin user, to check that access is checked when creating
    // shortcuts.
    $this->drupalLogin($this->shortcutUser);
    $title = $this->randomMachineName();
    $form_data = [
      'title[0][value]' => $title,
      'link[0][uri]' => '/admin',
    ];
    $this->drupalPostForm('admin/config/user-interface/shortcut/manage/' . $set->id() . '/add-link', $form_data, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertRaw(t("The path '@link_path' is inaccessible.", ['@link_path' => '/admin']));

    $form_data = [
      'title[0][value]' => $title,
      'link[0][uri]' => '/node',
    ];
    $this->drupalPostForm('admin/config/user-interface/shortcut/manage/' . $set->id() . '/add-link', $form_data, 'Save');
    $this->assertSession()->linkExists($title, 0, 'Shortcut link found on the page.');

    // Create a new shortcut set and add a link to it.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'label' => $this->randomMachineName(),
      'id' => strtolower($this->randomMachineName()),
    ];
    $this->drupalPostForm('admin/config/user-interface/shortcut/add-set', $edit, 'Save');
    $title = $this->randomMachineName();
    $form_data = [
      'title[0][value]' => $title,
      'link[0][uri]' => '/admin',
    ];
    $this->drupalPostForm('admin/config/user-interface/shortcut/manage/' . $edit['id'] . '/add-link', $form_data, 'Save');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that the "add to shortcut" and "remove from shortcut" links work.
   */
  public function testShortcutQuickLink() {
    \Drupal::service('theme_installer')->install(['seven']);
    $this->config('system.theme')->set('admin', 'seven')->save();
    $this->config('node.settings')->set('use_admin_theme', '1')->save();
    $this->container->get('router.builder')->rebuild();

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/config/system/cron');

    // Test the "Add to shortcuts" link.
    $this->clickLink('Add to Default shortcuts');
    $this->assertText('Added a shortcut for Cron.');
    $this->assertSession()->linkExists('Cron', 0, 'Shortcut link found on page');

    $this->drupalGet('admin/structure');
    $this->assertSession()->linkExists('Cron', 0, 'Shortcut link found on different page');

    // Test the "Remove from shortcuts" link.
    $this->clickLink('Cron');
    $this->clickLink('Remove from Default shortcuts');
    $this->assertText('The shortcut Cron has been deleted.');
    $this->assertSession()->linkNotExists('Cron', 'Shortcut link removed from page');

    $this->drupalGet('admin/structure');
    $this->assertSession()->linkNotExists('Cron', 'Shortcut link removed from different page');

    $this->drupalGet('admin/people');

    // Test the "Add to shortcuts" link for a page generated by views.
    $this->clickLink('Add to Default shortcuts');
    $this->assertText('Added a shortcut for People.');
    $this->assertShortcutQuickLink('Remove from Default shortcuts');

    // Test the "Remove from  shortcuts" link for a page generated by views.
    $this->clickLink('Remove from Default shortcuts');
    $this->assertText('The shortcut People has been deleted.');
    $this->assertShortcutQuickLink('Add to Default shortcuts');

    // Test two pages which use same route name but different route parameters.
    $this->drupalGet('node/add/page');
    // Add Shortcut for Basic Page.
    $this->clickLink('Add to Default shortcuts');
    $this->assertText('Added a shortcut for Create Basic page.');
    // Assure that Article does not have its shortcut indicated as set.
    $this->drupalGet('node/add/article');
    $link = $this->xpath('//a[normalize-space()=:label]', [':label' => 'Remove from Default shortcuts']);
    $this->assertTrue(empty($link), 'Link Remove to Default shortcuts not found for Create Article page.');
    // Add Shortcut for Article.
    $this->clickLink('Add to Default shortcuts');
    $this->assertText('Added a shortcut for Create Article.');

    $this->config('system.theme')->set('default', 'seven')->save();
    $this->drupalGet('node/' . $this->node->id());
    $title = $this->node->getTitle();

    // Test the "Add to shortcuts" link for node view route.
    $this->clickLink('Add to Default shortcuts');
    $this->assertText("Added a shortcut for $title.");
    $this->assertShortcutQuickLink('Remove from Default shortcuts');

    // Test the "Remove from shortcuts" link for node view route.
    $this->clickLink('Remove from Default shortcuts');
    $this->assertText("The shortcut $title has been deleted.");
    $this->assertShortcutQuickLink('Add to Default shortcuts');

    \Drupal::service('module_installer')->install(['block_content']);
    BlockContentType::create([
      'id' => 'basic',
      'label' => 'Basic block',
      'revision' => FALSE,
    ])->save();
    // Test page with HTML tags in title.
    $this->drupalGet('admin/structure/block/block-content/manage/basic');
    $page_title = new FormattableMarkup('Edit %label custom block type', ['%label' => 'Basic block']);
    $this->assertRaw($page_title);
    // Add shortcut to this page.
    $this->clickLink('Add to Default shortcuts');
    $this->assertRaw(new FormattableMarkup('Added a shortcut for %title.', [
      '%title' => trim(strip_tags($page_title)),
    ]));

  }

  /**
   * Tests that shortcut links can be renamed.
   */
  public function testShortcutLinkRename() {
    $set = $this->set;

    // Attempt to rename shortcut link.
    $new_link_name = $this->randomMachineName();

    $shortcuts = $set->getShortcuts();
    $shortcut = reset($shortcuts);
    $this->drupalPostForm('admin/config/user-interface/shortcut/link/' . $shortcut->id(), ['title[0][value]' => $new_link_name], 'Save');
    $saved_set = ShortcutSet::load($set->id());
    $titles = $this->getShortcutInformation($saved_set, 'title');
    $this->assertContains($new_link_name, $titles, 'Shortcut renamed: ' . $new_link_name);
    $this->assertSession()->linkExists($new_link_name, 0, 'Renamed shortcut link appears on the page.');
    $this->assertText('The shortcut ' . $new_link_name . ' has been updated.');
  }

  /**
   * Tests that changing the path of a shortcut link works.
   */
  public function testShortcutLinkChangePath() {
    $set = $this->set;

    // Tests changing a shortcut path.
    $new_link_path = '/admin/config';

    $shortcuts = $set->getShortcuts();
    $shortcut = reset($shortcuts);
    $this->drupalPostForm('admin/config/user-interface/shortcut/link/' . $shortcut->id(), ['title[0][value]' => $shortcut->getTitle(), 'link[0][uri]' => $new_link_path], 'Save');
    $saved_set = ShortcutSet::load($set->id());
    $paths = $this->getShortcutInformation($saved_set, 'link');
    $this->assertContains('internal:' . $new_link_path, $paths, 'Shortcut path changed: ' . $new_link_path);
    $this->assertSession()->linkByHrefExists($new_link_path, 0, 'Shortcut with new path appears on the page.');
    $this->assertText('The shortcut ' . $shortcut->getTitle() . ' has been updated.');
  }

  /**
   * Tests that changing the route of a shortcut link works.
   */
  public function testShortcutLinkChangeRoute() {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(200);
    // Disable the view.
    View::load('content')->disable()->save();
    /** @var \Drupal\Core\Routing\RouteBuilderInterface $router_builder */
    $router_builder = \Drupal::service('router.builder');
    $router_builder->rebuildIfNeeded();
    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests deleting a shortcut link.
   */
  public function testShortcutLinkDelete() {
    $set = $this->set;

    $shortcuts = $set->getShortcuts();
    $shortcut = reset($shortcuts);
    $this->drupalPostForm('admin/config/user-interface/shortcut/link/' . $shortcut->id() . '/delete', [], 'Delete');
    $saved_set = ShortcutSet::load($set->id());
    $ids = $this->getShortcutInformation($saved_set, 'id');
    $this->assertNotContains($shortcut->id(), $ids, 'Successfully deleted a shortcut.');

    // Delete all the remaining shortcut links.
    $storage = \Drupal::entityTypeManager()->getStorage('shortcut');
    $storage->delete($storage->loadMultiple(array_filter($ids)));

    // Get the front page to check that no exceptions occur.
    $this->drupalGet('');
  }

  /**
   * Tests that the add shortcut link is not displayed for 404/403 errors.
   *
   * Tests that the "Add to shortcuts" link is not displayed on a page not
   * found or a page the user does not have access to.
   */
  public function testNoShortcutLink() {
    // Change to a theme that displays shortcuts.
    \Drupal::service('theme_installer')->install(['seven']);
    $this->config('system.theme')
      ->set('default', 'seven')
      ->save();

    $this->drupalGet('page-that-does-not-exist');
    $result = $this->xpath('//a[contains(@class, "shortcut-action--add")]');
    $this->assertTrue(empty($result), 'Add to shortcuts link was not shown on a page not found.');

    // The user does not have access to this path.
    $this->drupalGet('admin/modules');
    $result = $this->xpath('//a[contains(@class, "shortcut-action--add")]');
    $this->assertTrue(empty($result), 'Add to shortcuts link was not shown on a page the user does not have access to.');

    // Verify that the testing mechanism works by verifying the shortcut link
    // appears on admin/content.
    $this->drupalGet('admin/content');
    $result = $this->xpath('//a[contains(@class, "shortcut-action--remove")]');
    $this->assertTrue(!empty($result), 'Remove from shortcuts link was shown on a page the user does have access to.');

    // Verify that the shortcut link appears on routing only pages.
    $this->drupalGet('router_test/test2');
    $result = $this->xpath('//a[contains(@class, "shortcut-action--add")]');
    $this->assertTrue(!empty($result), 'Add to shortcuts link was shown on a page the user does have access to.');
  }

  /**
   * Tests that the 'access shortcuts' permissions works properly.
   */
  public function testAccessShortcutsPermission() {
    // Change to a theme that displays shortcuts.
    \Drupal::service('theme_installer')->install(['seven']);
    $this->config('system.theme')
      ->set('default', 'seven')
      ->save();

    // Add cron to the default shortcut set.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/config/system/cron');
    $this->clickLink('Add to Default shortcuts');

    // Verify that users without the 'access shortcuts' permission can't see the
    // shortcuts.
    $this->drupalLogin($this->drupalCreateUser(['access toolbar']));
    $this->assertSession()->linkNotExists('Shortcuts', 'Shortcut link not found on page.');

    // Verify that users without the 'administer site configuration' permission
    // can't see the cron shortcuts but can see shortcuts.
    $this->drupalLogin($this->drupalCreateUser([
      'access toolbar',
      'access shortcuts',
    ]));
    $this->assertSession()->linkExists('Shortcuts');
    $this->assertSession()->linkNotExists('Cron', 'Cron shortcut link not found on page.');

    // Verify that users with the 'access shortcuts' permission can see the
    // shortcuts.
    $this->drupalLogin($this->drupalCreateUser([
      'access toolbar', 'access shortcuts', 'administer site configuration',
    ]));
    $this->clickLink('Shortcuts', 0, 'Shortcut link found on page.');
    $this->assertSession()->linkExists('Cron', 0, 'Cron shortcut link found on page.');

    $this->verifyAccessShortcutsPermissionForEditPages();
  }

  /**
   * Tests the shortcuts are correctly ordered by weight in the toolbar.
   */
  public function testShortcutLinkOrder() {
    // Ensure to give permissions to access the shortcuts.
    $this->drupalLogin($this->drupalCreateUser([
      'access toolbar',
      'access shortcuts',
      'access content overview',
      'administer content types',
    ]));
    $this->drupalGet(Url::fromRoute('<front>'));
    $shortcuts = $this->cssSelect('#toolbar-item-shortcuts-tray .toolbar-menu a');
    $this->assertEqual($shortcuts[0]->getText(), 'Add content');
    $this->assertEqual($shortcuts[1]->getText(), 'All content');
    foreach ($this->set->getShortcuts() as $shortcut) {
      $shortcut->setWeight($shortcut->getWeight() * -1)->save();
    }
    $this->drupalGet(Url::fromRoute('<front>'));
    $shortcuts = $this->cssSelect('#toolbar-item-shortcuts-tray .toolbar-menu a');
    $this->assertEqual($shortcuts[0]->getText(), 'All content');
    $this->assertEqual($shortcuts[1]->getText(), 'Add content');
  }

  /**
   * Tests that the 'access shortcuts' permission is required for shortcut set
   * administration page access.
   */
  private function verifyAccessShortcutsPermissionForEditPages() {
    // Create a user with customize links and switch sets permissions  but
    // without the 'access shortcuts' permission.
    $test_permissions = [
      'customize shortcut links',
      'switch shortcut sets',
    ];
    $noaccess_user = $this->drupalCreateUser($test_permissions);
    $this->drupalLogin($noaccess_user);

    // Verify that set administration pages are inaccessible without the
    // 'access shortcuts' permission.
    $this->drupalGet('admin/config/user-interface/shortcut/manage/default/customize');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/user-interface/shortcut/manage/default');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('user/' . $noaccess_user->id() . '/shortcuts');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that the 'access shortcuts' permission is required to access the
   * shortcut block.
   */
  public function testShortcutBlockAccess() {
    // Creates a block instance and place in a region through api.
    $block = $this->drupalPlaceBlock('shortcuts');

    // Verify that users with the 'access shortcuts' permission can see the
    // shortcut block.
    $this->drupalLogin($this->shortcutUser);
    $this->drupalGet('');
    $this->assertBlockAppears($block);

    $this->drupalLogout();

    // Verify that users without the 'access shortcuts' permission can see the
    // shortcut block.
    $this->drupalLogin($this->drupalCreateUser([]));
    $this->drupalGet('');
    $this->assertNoBlockAppears($block);
  }

  /**
   * Passes if a shortcut quick link with the specified label is found.
   *
   * An optional link index may be passed.
   *
   * @param string $label
   *   Text between the anchor tags.
   * @param int $index
   *   Link position counting from zero.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use new FormattableMarkup() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE if the assertion succeeded.
   */
  protected function assertShortcutQuickLink($label, $index = 0, $message = '', $group = 'Other') {
    $links = $this->xpath('//a[normalize-space()=:label]', [':label' => $label]);
    $message = ($message ? $message : new FormattableMarkup('Shortcut quick link with label %label found.', ['%label' => $label]));
    $this->assertArrayHasKey($index, $links, $message);
    return TRUE;
  }

}
