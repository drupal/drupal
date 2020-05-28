<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests output on administrative pages and compact mode functionality.
 *
 * @group system
 */
class AdminTest extends BrowserTestBase {

  /**
   * User account with all available permissions
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * User account with limited access to administration pages.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $webUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['locale'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    // testAdminPages() requires Locale module.
    parent::setUp();

    // Create an administrator with all permissions, as well as a regular user
    // who can only access administration pages and perform some Locale module
    // administrative tasks, but not all of them.
    $this->adminUser = $this->drupalCreateUser(array_keys(\Drupal::service('user.permissions')->getPermissions()));
    $this->webUser = $this->drupalCreateUser([
      'access administration pages',
      'translate interface',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests output on administrative listing pages.
   */
  public function testAdminPages() {
    // Go to Administration.
    $this->drupalGet('admin');

    // Verify that all visible, top-level administration links are listed on
    // the main administration page.
    foreach ($this->getTopLevelMenuLinks() as $item) {
      $this->assertLink($item->getTitle());
      $this->assertLinkByHref($item->getUrlObject()->toString());
      // The description should appear below the link.
      $this->assertText($item->getDescription());
    }

    // For each administrative listing page on which the Locale module appears,
    // verify that there are links to the module's primary configuration pages,
    // but no links to its individual sub-configuration pages. Also verify that
    // a user with access to only some Locale module administration pages only
    // sees links to the pages they have access to.
    $admin_list_pages = [
      'admin/index',
      'admin/config',
      'admin/config/regional',
    ];

    foreach ($admin_list_pages as $page) {
      // For the administrator, verify that there are links to Locale's primary
      // configuration pages, but no links to individual sub-configuration
      // pages.
      $this->drupalLogin($this->adminUser);
      $this->drupalGet($page);
      $this->assertLinkByHref('admin/config');
      $this->assertLinkByHref('admin/config/regional/settings');
      $this->assertLinkByHref('admin/config/regional/date-time');
      $this->assertLinkByHref('admin/config/regional/language');
      $this->assertNoLinkByHref('admin/config/regional/language/detection/session');
      $this->assertNoLinkByHref('admin/config/regional/language/detection/url');
      $this->assertLinkByHref('admin/config/regional/translate');
      // On admin/index only, the administrator should also see a "Configure
      // permissions" link for the Locale module.
      if ($page == 'admin/index') {
        $this->assertLinkByHref("admin/people/permissions#module-locale");
      }

      // For a less privileged user, verify that there are no links to Locale's
      // primary configuration pages, but a link to the translate page exists.
      $this->drupalLogin($this->webUser);
      $this->drupalGet($page);
      $this->assertLinkByHref('admin/config');
      $this->assertNoLinkByHref('admin/config/regional/settings');
      $this->assertNoLinkByHref('admin/config/regional/date-time');
      $this->assertNoLinkByHref('admin/config/regional/language');
      $this->assertNoLinkByHref('admin/config/regional/language/detection/session');
      $this->assertNoLinkByHref('admin/config/regional/language/detection/url');
      $this->assertLinkByHref('admin/config/regional/translate');
      // This user cannot configure permissions, so even on admin/index should
      // not see a "Configure permissions" link for the Locale module.
      if ($page == 'admin/index') {
        $this->assertNoLinkByHref("admin/people/permissions#module-locale");
      }
    }
  }

  /**
   * Returns all top level menu links.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface[]
   */
  protected function getTopLevelMenuLinks() {
    $menu_tree = \Drupal::menuTree();

    // The system.admin link is normally the parent of all top-level admin links.
    $parameters = new MenuTreeParameters();
    $parameters->setRoot('system.admin')->excludeRoot()->setTopLevelOnly()->onlyEnabledLinks();
    $tree = $menu_tree->load(NULL, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:flatten'],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);

    // Transform the tree to a list of menu links.
    $menu_links = [];
    foreach ($tree as $element) {
      $menu_links[] = $element->link;
    }

    return $menu_links;
  }

  /**
   * Test compact mode.
   */
  public function testCompactMode() {
    $session = $this->getSession();

    // The front page defaults to 'user/login', which redirects to 'user/{user}'
    // for authenticated users. We cannot use '<front>', since this does not
    // match the redirected url.
    $frontpage_url = 'user/' . $this->adminUser->id();

    $this->drupalGet('admin/compact/on');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertUrl($frontpage_url, [], 'The user is redirected to the front page after turning on compact mode.');
    $this->assertEquals('1', $session->getCookie('Drupal.visitor.admin_compact_mode'), 'Compact mode turns on.');
    $this->drupalGet('admin/compact/on');
    $this->assertEquals('1', $session->getCookie('Drupal.visitor.admin_compact_mode'), 'Compact mode remains on after a repeat call.');
    $this->drupalGet('');
    $this->assertEquals('1', $session->getCookie('Drupal.visitor.admin_compact_mode'), 'Compact mode persists on new requests.');

    $this->drupalGet('admin/compact/off');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertUrl($frontpage_url, [], 'The user is redirected to the front page after turning off compact mode.');
    $this->assertNull($session->getCookie('Drupal.visitor.admin_compact_mode'), 'Compact mode turns off.');
    $this->drupalGet('admin/compact/off');
    $this->assertNull($session->getCookie('Drupal.visitor.admin_compact_mode'), 'Compact mode remains off after a repeat call.');
    $this->drupalGet('');
    $this->assertNull($session->getCookie('Drupal.visitor.admin_compact_mode'), 'Compact mode persists off new requests.');
  }

}
