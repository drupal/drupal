<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\AdminTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests administrative overview pages.
 */
class AdminTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Administrative pages',
      'description' => 'Tests output on administrative pages and compact mode functionality.',
      'group' => 'System',
    );
  }

  function setUp() {
    // testAdminPages() requires Locale module.
    parent::setUp(array('locale'));

    // Create an administrator with all permissions, as well as a regular user
    // who can only access administration pages and perform some Locale module
    // administrative tasks, but not all of them.
    $this->admin_user = $this->drupalCreateUser(array_keys(module_invoke_all('permission')));
    $this->web_user = $this->drupalCreateUser(array(
      'access administration pages',
      'translate interface',
    ));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests output on administrative listing pages.
   */
  function testAdminPages() {
    // Go to Administration.
    $this->drupalGet('admin');

    // Verify that all visible, top-level administration links are listed on
    // the main administration page.
    foreach (menu_get_router() as $path => $item) {
      if (strpos($path, 'admin/') === 0 && ($item['type'] & MENU_VISIBLE_IN_TREE) && $item['_number_parts'] == 2) {
        $this->assertLink($item['title']);
        $this->assertLinkByHref($path);
        $this->assertText($item['description']);
      }
    }

    // For each administrative listing page on which the Locale module appears,
    // verify that there are links to the module's primary configuration pages,
    // but no links to its individual sub-configuration pages. Also verify that
    // a user with access to only some Locale module administration pages only
    // sees links to the pages they have access to.
    $admin_list_pages = array(
      'admin/index',
      'admin/config',
      'admin/config/regional',
    );

    foreach ($admin_list_pages as $page) {
      // For the administrator, verify that there are links to Locale's primary
      // configuration pages, but no links to individual sub-configuration
      // pages.
      $this->drupalLogin($this->admin_user);
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
      $this->drupalLogin($this->web_user);
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
   * Test compact mode.
   */
  function testCompactMode() {
    $this->drupalGet('admin/compact/on');
    $this->assertTrue($this->cookies['Drupal.visitor.admin_compact_mode']['value'], t('Compact mode turns on.'));
    $this->drupalGet('admin/compact/on');
    $this->assertTrue($this->cookies['Drupal.visitor.admin_compact_mode']['value'], t('Compact mode remains on after a repeat call.'));
    $this->drupalGet('');
    $this->assertTrue($this->cookies['Drupal.visitor.admin_compact_mode']['value'], t('Compact mode persists on new requests.'));

    $this->drupalGet('admin/compact/off');
    $this->assertEqual($this->cookies['Drupal.visitor.admin_compact_mode']['value'], 'deleted', t('Compact mode turns off.'));
    $this->drupalGet('admin/compact/off');
    $this->assertEqual($this->cookies['Drupal.visitor.admin_compact_mode']['value'], 'deleted', t('Compact mode remains off after a repeat call.'));
    $this->drupalGet('');
    $this->assertTrue($this->cookies['Drupal.visitor.admin_compact_mode']['value'], t('Compact mode persists on new requests.'));
  }
}
