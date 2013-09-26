<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Menu\BreadcrumbTest.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\Component\Utility\Unicode;

/**
 * Menu breadcrumbs related tests.
 */
class BreadcrumbTest extends MenuTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('menu_test', 'block');

  /**
   * Test paths in the Standard profile.
   */
  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'Breadcrumbs',
      'description' => 'Tests breadcrumbs functionality.',
      'group' => 'Menu',
    );
  }

  function setUp() {
    parent::setUp();

    $perms = array_keys(module_invoke_all('permission'));
    $this->admin_user = $this->drupalCreateUser($perms);
    $this->drupalLogin($this->admin_user);

    // This test puts menu links in the Tools menu and then tests for their
    // presence on the page, so we need to ensure that the Tools block will be
    // displayed in the default theme and admin theme.
    $settings = array(
      'machine_name' => 'system_menu_tools',
      'region' => 'content',
    );
    $this->drupalPlaceBlock('system_menu_block:tools', $settings);
    $settings['theme'] = \Drupal::config('system.theme')->get('admin');
    $this->drupalPlaceBlock('system_menu_block:tools', $settings);
  }

  /**
   * Tests breadcrumbs on node and administrative paths.
   */
  function testBreadCrumbs() {
    // Prepare common base breadcrumb elements.
    $home = array('' => 'Home');
    $admin = $home + array('admin' => t('Administration'));
    $config = $admin + array('admin/config' => t('Configuration'));
    $type = 'article';

    // Verify Taxonomy administration breadcrumbs.
    $trail = $admin + array(
      'admin/structure' => t('Structure'),
    );
    $this->assertBreadcrumb('admin/structure/taxonomy', $trail);

    $trail += array(
      'admin/structure/taxonomy' => t('Taxonomy'),
    );
    $this->assertBreadcrumb('admin/structure/taxonomy/manage/tags', $trail);
    $trail += array(
      'admin/structure/taxonomy/manage/tags' => t('Tags'),
    );
    $this->assertBreadcrumb('admin/structure/taxonomy/manage/tags/edit', $trail);
    $this->assertBreadcrumb('admin/structure/taxonomy/manage/tags/fields', $trail);
    $this->assertBreadcrumb('admin/structure/taxonomy/manage/tags/add', $trail);

    // Verify Menu administration breadcrumbs.
    $trail = $admin + array(
      'admin/structure' => t('Structure'),
    );
    $this->assertBreadcrumb('admin/structure/menu', $trail);

    $trail += array(
      'admin/structure/menu' => t('Menus'),
    );
    $this->assertBreadcrumb('admin/structure/menu/manage/tools', $trail);

    $mlid_node_add = db_query('SELECT mlid FROM {menu_links} WHERE link_path = :href AND module = :module', array(
      ':href' => 'node/add',
      ':module' => 'system',
    ))->fetchField();
    $trail += array(
      'admin/structure/menu/manage/tools' => t('Tools'),
    );
    $this->assertBreadcrumb("admin/structure/menu/item/$mlid_node_add/edit", $trail);
    $this->assertBreadcrumb('admin/structure/menu/manage/tools/add', $trail);

    // Verify Node administration breadcrumbs.
    $trail = $admin + array(
      'admin/structure' => t('Structure'),
      'admin/structure/types' => t('Content types'),
    );
    $this->assertBreadcrumb('admin/structure/types/add', $trail);
    $this->assertBreadcrumb("admin/structure/types/manage/$type", $trail);
    $trail += array(
      "admin/structure/types/manage/$type" => t('Article'),
    );
    $this->assertBreadcrumb("admin/structure/types/manage/$type/fields", $trail);
    $this->assertBreadcrumb("admin/structure/types/manage/$type/display", $trail);
    $trail_teaser = $trail + array(
      "admin/structure/types/manage/$type/display" => t('Manage display'),
    );
    $this->assertBreadcrumb("admin/structure/types/manage/$type/display/teaser", $trail_teaser);
    $this->assertBreadcrumb("admin/structure/types/manage/$type/delete", $trail);
    $trail += array(
      "admin/structure/types/manage/$type/fields" => t('Manage fields'),
    );
    $this->assertBreadcrumb("admin/structure/types/manage/$type/fields/node.$type.body", $trail);

    // Verify Filter text format administration breadcrumbs.
    $filter_formats = filter_formats();
    $format = reset($filter_formats);
    $format_id = $format->format;
    $trail = $config + array(
      'admin/config/content' => t('Content authoring'),
    );
    $this->assertBreadcrumb('admin/config/content/formats', $trail);

    $trail += array(
      'admin/config/content/formats' => t('Text formats and editors'),
    );
    $this->assertBreadcrumb('admin/config/content/formats/add', $trail);
    $this->assertBreadcrumb("admin/config/content/formats/manage/$format_id", $trail);
    // @todo Remove this part once we have a _title_callback, see
    //   https://drupal.org/node/2076085.
    $trail += array(
      "admin/config/content/formats/manage/$format_id" => Unicode::ucfirst(Unicode::strtolower($format->name)),
    );
    $this->assertBreadcrumb("admin/config/content/formats/manage/$format_id/disable", $trail);

    // Verify node breadcrumbs (without menu link).
    $node1 = $this->drupalCreateNode();
    $nid1 = $node1->id();
    $trail = $home;
    $this->assertBreadcrumb("node/$nid1", $trail);
    // Also verify that the node does not appear elsewhere (e.g., menu trees).
    $this->assertNoLink($node1->getTitle());
    // Also verify that the node does not appear elsewhere (e.g., menu trees).
    $this->assertNoLink($node1->getTitle());

    $trail += array(
      "node/$nid1" => $node1->getTitle(),
    );
    $this->assertBreadcrumb("node/$nid1/edit", $trail);

    // Verify that breadcrumb on node listing page contains "Home" only.
    $trail = array();
    $this->assertBreadcrumb('node', $trail);

    // Verify node breadcrumbs (in menu).
    // Do this separately for Main menu and Tools menu, since only the
    // latter is a preferred menu by default.
    // @todo Also test all themes? Manually testing led to the suspicion that
    //   breadcrumbs may differ, possibly due to theme overrides.
    $menus = array('main', 'tools');
    // Alter node type menu settings.
    variable_set("menu_options_$type", $menus);
    variable_set("menu_parent_$type", 'tools:0');

    foreach ($menus as $menu) {
      // Create a parent node in the current menu.
      $title = $this->randomName();
      $node2 = $this->drupalCreateNode(array(
        'type' => $type,
        'title' => $title,
        'menu' => entity_create('menu_link', array(
          'enabled' => 1,
          'link_title' => 'Parent ' . $title,
          'description' => '',
          'menu_name' => $menu,
          'plid' => 0,
        )),
      ));

      if ($menu == 'tools') {
        $parent = $node2;
      }
    }

    // Create a Tools menu link for 'node', move the last parent node menu
    // link below it, and verify a full breadcrumb for the last child node.
    $menu = 'tools';
    $edit = array(
      'link_title' => 'Root',
      'link_path' => 'node',
    );
    $this->drupalPostForm("admin/structure/menu/manage/$menu/add", $edit, t('Save'));
    $menu_links = entity_load_multiple_by_properties('menu_link', array('link_title' => 'Root'));
    $link = reset($menu_links);

    $edit = array(
      'menu[parent]' => $link['menu_name'] . ':' . $link['mlid'],
    );
    $this->drupalPostForm('node/' . $parent->id() . '/edit', $edit, t('Save and keep published'));
    $expected = array(
      "node" => $link['link_title'],
    );
    $trail = $home + $expected;
    $tree = $expected + array(
      'node/' . $parent->id() => $parent->menu['link_title'],
    );
    $trail += array(
      'node/' . $parent->id() => $parent->menu['link_title'],
    );

    // Add a taxonomy term/tag to last node, and add a link for that term to the
    // Tools menu.
    $tags = array(
      'Drupal' => array(),
      'Breadcrumbs' => array(),
    );
    $edit = array(
      'field_tags' => implode(',', array_keys($tags)),
    );
    $this->drupalPostForm('node/' . $parent->id() . '/edit', $edit, t('Save and keep published'));

    // Put both terms into a hierarchy Drupal » Breadcrumbs. Required for both
    // the menu links and the terms itself, since taxonomy_term_page() resets
    // the breadcrumb based on taxonomy term hierarchy.
    $parent_tid = 0;
    foreach ($tags as $name => $null) {
      $terms = entity_load_multiple_by_properties('taxonomy_term', array('name' => $name));
      $term = reset($terms);
      $tags[$name]['term'] = $term;
      if ($parent_tid) {
        $edit = array(
          'parent[]' => array($parent_tid),
        );
        $this->drupalPostForm("taxonomy/term/{$term->id()}/edit", $edit, t('Save'));
      }
      $parent_tid = $term->id();
    }
    $parent_mlid = 0;
    foreach ($tags as $name => $data) {
      $term = $data['term'];
      $edit = array(
        'link_title' => "$name link",
        'link_path' => "taxonomy/term/{$term->id()}",
        'parent' => "$menu:{$parent_mlid}",
      );
      $this->drupalPostForm("admin/structure/menu/manage/$menu/add", $edit, t('Save'));
      $menu_links = entity_load_multiple_by_properties('menu_link', array('link_title' => $edit['link_title'], 'link_path' => $edit['link_path']));
      $tags[$name]['link'] = reset($menu_links);
      $tags[$name]['link']['link_path'] = $edit['link_path'];
      $parent_mlid = $tags[$name]['link']['mlid'];
    }

    // Verify expected breadcrumbs for menu links.
    $trail = $home;
    $tree = array();
    foreach ($tags as $name => $data) {
      $term = $data['term'];
      $link = $data['link'];

      $tree += array(
        $link['link_path'] => $link['link_title'],
      );
      $this->assertBreadcrumb($link['link_path'], $trail, $term->label(), $tree);
      $this->assertRaw(check_plain($parent->getTitle()), 'Tagged node found.');

      // Additionally make sure that this link appears only once; i.e., the
      // untranslated menu links automatically generated from menu router items
      // ('taxonomy/term/%') should never be translated and appear in any menu
      // other than the breadcrumb trail.
      $elements = $this->xpath('//div[@id=:menu]/descendant::a[@href=:href]', array(
        ':menu' => 'block-system-menu-tools',
        ':href' => url($link['link_path']),
      ));
      $this->assertTrue(count($elements) == 1, "Link to {$link['link_path']} appears only once.");

      // Next iteration should expect this tag as parent link.
      // Note: Term name, not link name, due to taxonomy_term_page().
      $trail += array(
        $link['link_path'] => $term->label(),
      );
    }

    // Verify breadcrumbs on user and user/%.
    // We need to log back in and out below, and cannot simply grant the
    // 'administer users' permission, since user_page() makes your head explode.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array(
      'access user profiles',
    ));
    $this->drupalLogout();

    // Verify breadcrumb on front page.
    $this->assertBreadcrumb('<front>', array());

    // Verify breadcrumb on user pages (without menu link) for anonymous user.
    $trail = $home;
    $this->assertBreadcrumb('user', $trail, t('Log in'));
    $this->assertBreadcrumb('user/' . $this->admin_user->id(), $trail, $this->admin_user->getUsername());

    // Verify breadcrumb on user pages (without menu link) for registered users.
    $this->drupalLogin($this->admin_user);
    $trail = $home;
    $this->assertBreadcrumb('user', $trail, $this->admin_user->getUsername());
    $this->assertBreadcrumb('user/' . $this->admin_user->id(), $trail, $this->admin_user->getUsername());
    $trail += array(
      'user/' . $this->admin_user->id() => $this->admin_user->getUsername(),
    );
    $this->assertBreadcrumb('user/' . $this->admin_user->id() . '/edit', $trail, $this->admin_user->getUsername());

    // Create a second user to verify breadcrumb on user pages again.
    $this->web_user = $this->drupalCreateUser(array(
      'administer users',
      'access user profiles',
    ));
    $this->drupalLogin($this->web_user);

    // Verify correct breadcrumb and page title on another user's account pages
    // (without menu link).
    $trail = $home;
    $this->assertBreadcrumb('user/' . $this->admin_user->id(), $trail, $this->admin_user->getUsername());
    $trail += array(
      'user/' . $this->admin_user->id() => $this->admin_user->getUsername(),
    );
    $this->assertBreadcrumb('user/' . $this->admin_user->id() . '/edit', $trail, $this->admin_user->getUsername());

    // Verify correct breadcrumb and page title when viewing own user account
    // pages (without menu link).
    $trail = $home;
    $this->assertBreadcrumb('user/' . $this->web_user->id(), $trail, $this->web_user->getUsername());
    $trail += array(
      'user/' . $this->web_user->id() => $this->web_user->getUsername(),
    );
    $tree = array(
      'user' => t('My account'),
    );
    $this->assertBreadcrumb('user/' . $this->web_user->id() . '/edit', $trail, $this->web_user->getUsername(), $tree);

    // Add a Tools menu links for 'user' and $this->admin_user.
    // Although it may be faster to manage these links via low-level API
    // functions, there's a lot that can go wrong in doing so.
    $this->drupalLogin($this->admin_user);
    $edit = array(
      'link_title' => 'User',
      'link_path' => 'user',
    );
    $this->drupalPostForm("admin/structure/menu/manage/$menu/add", $edit, t('Save'));
    $menu_links_user = entity_load_multiple_by_properties('menu_link', array('link_title' => $edit['link_title'], 'link_path' => $edit['link_path']));
    $link_user = reset($menu_links_user);

    $edit = array(
      'link_title' => $this->admin_user->getUsername() . ' link',
      'link_path' => 'user/' . $this->admin_user->id(),
    );
    $this->drupalPostForm("admin/structure/menu/manage/$menu/add", $edit, t('Save'));
    $menu_links_admin_user = entity_load_multiple_by_properties('menu_link', array('link_title' => $edit['link_title'], 'link_path' => $edit['link_path']));
    $link_admin_user = reset($menu_links_admin_user);

    // Verify expected breadcrumbs for the two separate links.
    $this->drupalLogout();
    $trail = $home;
    $tree = array(
      $link_user['link_path'] => $link_user['link_title'],
    );
    $this->assertBreadcrumb('user', $trail, $link_user['link_title'], $tree);
    $tree = array(
      $link_admin_user['link_path'] => $link_admin_user['link_title'],
    );
    // $this->assertBreadcrumb('user/' . $this->admin_user->id(), $trail, $link_admin_user['link_title'], $tree);

    // Create an only slightly privileged user being able to access site reports
    // but not administration pages.
    $this->web_user = $this->drupalCreateUser(array(
      'access site reports',
    ));
    $this->drupalLogin($this->web_user);

    // Verify that we can access recent log entries, there is a corresponding
    // page title, and that the breadcrumb is just the Home link (because the
    // user is not able to access "Administer".
    $trail = $home;
    $this->assertBreadcrumb('admin', $trail, t('Access denied'));
    $this->assertResponse(403);

    // Since the 'admin' path is not accessible, we still expect only the Home
    // link.
    $this->assertBreadcrumb('admin/reports', $trail, t('Reports'));
    $this->assertNoResponse(403);

    // Since the Reports page is accessible, that will show.
    $trail += array('admin/reports' => t('Reports'));
    $this->assertBreadcrumb('admin/reports/dblog', $trail, t('Recent log messages'));
    $this->assertNoResponse(403);
  }
}
