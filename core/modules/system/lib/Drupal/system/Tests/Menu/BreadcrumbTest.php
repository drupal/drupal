<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Menu\BreadcrumbTest.
 */

namespace Drupal\system\Tests\Menu;

/**
 * Menu breadcrumbs related tests.
 */
class BreadcrumbTest extends MenuTestBase {
  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'Breadcrumbs',
      'description' => 'Tests breadcrumbs functionality.',
      'group' => 'Menu',
    );
  }

  function setUp() {
    parent::setUp(array('menu_test'));

    $perms = array_keys(module_invoke_all('permission'));
    $this->admin_user = $this->drupalCreateUser($perms);
    $this->drupalLogin($this->admin_user);

    // This test puts menu links in the Navigation menu and then tests for
    // their presence on the page, so we need to ensure that the Navigation
    // block will be displayed in all active themes.
    db_update('block')
      ->fields(array(
        // Use a region that is valid for all themes.
        'region' => 'content',
        'status' => 1,
      ))
      ->condition('module', 'system')
      ->condition('delta', 'navigation')
      ->execute();
  }

  /**
   * Tests breadcrumbs on node and administrative paths.
   */
  function testBreadCrumbs() {
    // Prepare common base breadcrumb elements.
    $home = array('<front>' => 'Home');
    $admin = $home + array('admin' => t('Administration'));
    $config = $admin + array('admin/config' => t('Configuration'));
    $type = 'article';
    $langcode = LANGUAGE_NOT_SPECIFIED;

    // Verify breadcrumbs for default local tasks.
    $expected = array(
      'menu-test' => t('Menu test root'),
    );
    $title = t('Breadcrumbs test: Local tasks');
    $trail = $home + $expected;
    $tree = $expected + array(
      'menu-test/breadcrumb/tasks' => $title,
    );
    $this->assertBreadcrumb('menu-test/breadcrumb/tasks', $trail, $title, $tree);
    $this->assertBreadcrumb('menu-test/breadcrumb/tasks/first', $trail, $title, $tree);
    $this->assertBreadcrumb('menu-test/breadcrumb/tasks/first/first', $trail, $title, $tree);
    $trail += array(
      'menu-test/breadcrumb/tasks' => t('Breadcrumbs test: Local tasks'),
    );
    $this->assertBreadcrumb('menu-test/breadcrumb/tasks/first/second', $trail, $title, $tree);
    $this->assertBreadcrumb('menu-test/breadcrumb/tasks/second', $trail, $title, $tree);
    $this->assertBreadcrumb('menu-test/breadcrumb/tasks/second/first', $trail, $title, $tree);
    $trail += array(
      'menu-test/breadcrumb/tasks/second' => t('Second'),
    );
    $this->assertBreadcrumb('menu-test/breadcrumb/tasks/second/second', $trail, $title, $tree);

    // Verify Taxonomy administration breadcrumbs.
    $trail = $admin + array(
      'admin/structure' => t('Structure'),
    );
    $this->assertBreadcrumb('admin/structure/taxonomy', $trail);

    $trail += array(
      'admin/structure/taxonomy' => t('Taxonomy'),
    );
    $this->assertBreadcrumb('admin/structure/taxonomy/tags', $trail);
    $trail += array(
      'admin/structure/taxonomy/tags' => t('Tags'),
    );
    $this->assertBreadcrumb('admin/structure/taxonomy/tags/edit', $trail);
    $this->assertBreadcrumb('admin/structure/taxonomy/tags/fields', $trail);
    $this->assertBreadcrumb('admin/structure/taxonomy/tags/add', $trail);

    // Verify Menu administration breadcrumbs.
    $trail = $admin + array(
      'admin/structure' => t('Structure'),
    );
    $this->assertBreadcrumb('admin/structure/menu', $trail);

    $trail += array(
      'admin/structure/menu' => t('Menus'),
    );
    $this->assertBreadcrumb('admin/structure/menu/manage/navigation', $trail);
    $trail += array(
      'admin/structure/menu/manage/navigation' => t('Navigation'),
    );
    $this->assertBreadcrumb("admin/structure/menu/item/6/edit", $trail);
    $this->assertBreadcrumb('admin/structure/menu/manage/navigation/edit', $trail);
    $this->assertBreadcrumb('admin/structure/menu/manage/navigation/add', $trail);

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
    $this->assertBreadcrumb("admin/structure/types/manage/$type/comment/fields", $trail);
    $this->assertBreadcrumb("admin/structure/types/manage/$type/comment/display", $trail);
    $this->assertBreadcrumb("admin/structure/types/manage/$type/delete", $trail);
    $trail += array(
      "admin/structure/types/manage/$type/fields" => t('Manage fields'),
    );
    $this->assertBreadcrumb("admin/structure/types/manage/$type/fields/body", $trail);
    $trail += array(
      "admin/structure/types/manage/$type/fields/body" => t('Body'),
    );
    $this->assertBreadcrumb("admin/structure/types/manage/$type/fields/body/widget-type", $trail);

    // Verify Filter text format administration breadcrumbs.
    $format = db_query_range("SELECT format, name FROM {filter_format}", 1, 1)->fetch();
    $format_id = $format->format;
    $trail = $config + array(
      'admin/config/content' => t('Content authoring'),
    );
    $this->assertBreadcrumb('admin/config/content/formats', $trail);

    $trail += array(
      'admin/config/content/formats' => t('Text formats'),
    );
    $this->assertBreadcrumb('admin/config/content/formats/add', $trail);
    $this->assertBreadcrumb("admin/config/content/formats/$format_id", $trail);
    $trail += array(
      "admin/config/content/formats/$format_id" => $format->name,
    );
    $this->assertBreadcrumb("admin/config/content/formats/$format_id/disable", $trail);

    // Verify node breadcrumbs (without menu link).
    $node1 = $this->drupalCreateNode();
    $nid1 = $node1->nid;
    $trail = $home;
    $this->assertBreadcrumb("node/$nid1", $trail);
    // Also verify that the node does not appear elsewhere (e.g., menu trees).
    $this->assertNoLink($node1->title);
    // The node itself should not be contained in the breadcrumb on the default
    // local task, since there is no difference between both pages.
    $this->assertBreadcrumb("node/$nid1/view", $trail);
    // Also verify that the node does not appear elsewhere (e.g., menu trees).
    $this->assertNoLink($node1->title);

    $trail += array(
      "node/$nid1" => $node1->title,
    );
    $this->assertBreadcrumb("node/$nid1/edit", $trail);

    // Verify that breadcrumb on node listing page contains "Home" only.
    $trail = array();
    $this->assertBreadcrumb('node', $trail);

    // Verify node breadcrumbs (in menu).
    // Do this separately for Main menu and Navigation menu, since only the
    // latter is a preferred menu by default.
    // @todo Also test all themes? Manually testing led to the suspicion that
    //   breadcrumbs may differ, possibly due to template.php overrides.
    $menus = array('main-menu', 'navigation');
    // Alter node type menu settings.
    variable_set("menu_options_$type", $menus);
    variable_set("menu_parent_$type", 'navigation:0');

    foreach ($menus as $menu) {
      // Create a parent node in the current menu.
      $title = $this->randomName();
      $node2 = $this->drupalCreateNode(array(
        'type' => $type,
        'title' => $title,
        'menu' => array(
          'enabled' => 1,
          'link_title' => 'Parent ' . $title,
          'description' => '',
          'menu_name' => $menu,
          'plid' => 0,
        ),
      ));
      $nid2 = $node2->nid;

      $trail = $home;
      $tree = array(
        "node/$nid2" => $node2->menu['link_title'],
      );
      $this->assertBreadcrumb("node/$nid2", $trail, $node2->title, $tree);
      // The node itself should not be contained in the breadcrumb on the
      // default local task, since there is no difference between both pages.
      $this->assertBreadcrumb("node/$nid2/view", $trail, $node2->title, $tree);
      $trail += array(
        "node/$nid2" => $node2->menu['link_title'],
      );
      $this->assertBreadcrumb("node/$nid2/edit", $trail);

      // Create a child node in the current menu.
      $title = $this->randomName();
      $node3 = $this->drupalCreateNode(array(
        'type' => $type,
        'title' => $title,
        'menu' => array(
          'enabled' => 1,
          'link_title' => 'Child ' . $title,
          'description' => '',
          'menu_name' => $menu,
          'plid' => $node2->menu['mlid'],
        ),
      ));
      $nid3 = $node3->nid;

      $this->assertBreadcrumb("node/$nid3", $trail, $node3->title, $tree, FALSE);
      // The node itself should not be contained in the breadcrumb on the
      // default local task, since there is no difference between both pages.
      $this->assertBreadcrumb("node/$nid3/view", $trail, $node3->title, $tree, FALSE);
      $trail += array(
        "node/$nid3" => $node3->menu['link_title'],
      );
      $tree += array(
        "node/$nid3" => $node3->menu['link_title'],
      );
      $this->assertBreadcrumb("node/$nid3/edit", $trail);

      // Verify that node listing page still contains "Home" only.
      $trail = array();
      $this->assertBreadcrumb('node', $trail);

      if ($menu == 'navigation') {
        $parent = $node2;
        $child = $node3;
      }
    }

    // Create a Navigation menu link for 'node', move the last parent node menu
    // link below it, and verify a full breadcrumb for the last child node.
    $menu = 'navigation';
    $edit = array(
      'link_title' => 'Root',
      'link_path' => 'node',
    );
    $this->drupalPost("admin/structure/menu/manage/$menu/add", $edit, t('Save'));
    $link = db_query('SELECT * FROM {menu_links} WHERE link_title = :title', array(':title' => 'Root'))->fetchAssoc();

    $edit = array(
      'menu[parent]' => $link['menu_name'] . ':' . $link['mlid'],
    );
    $this->drupalPost("node/{$parent->nid}/edit", $edit, t('Save'));
    $expected = array(
      "node" => $link['link_title'],
    );
    $trail = $home + $expected;
    $tree = $expected + array(
      "node/{$parent->nid}" => $parent->menu['link_title'],
    );
    $this->assertBreadcrumb(NULL, $trail, $parent->title, $tree);
    $trail += array(
      "node/{$parent->nid}" => $parent->menu['link_title'],
    );
    $tree += array(
      "node/{$child->nid}" => $child->menu['link_title'],
    );
    $this->assertBreadcrumb("node/{$child->nid}", $trail, $child->title, $tree);

    // Add a taxonomy term/tag to last node, and add a link for that term to the
    // Navigation menu.
    $tags = array(
      'Drupal' => array(),
      'Breadcrumbs' => array(),
    );
    $edit = array(
      "field_tags[$langcode]" => implode(',', array_keys($tags)),
    );
    $this->drupalPost("node/{$parent->nid}/edit", $edit, t('Save'));

    // Put both terms into a hierarchy Drupal » Breadcrumbs. Required for both
    // the menu links and the terms itself, since taxonomy_term_page() resets
    // the breadcrumb based on taxonomy term hierarchy.
    $parent_tid = 0;
    foreach ($tags as $name => $null) {
      $terms = taxonomy_term_load_multiple(FALSE, array('name' => $name));
      $term = reset($terms);
      $tags[$name]['term'] = $term;
      if ($parent_tid) {
        $edit = array(
          'parent[]' => array($parent_tid),
        );
        $this->drupalPost("taxonomy/term/{$term->tid}/edit", $edit, t('Save'));
      }
      $parent_tid = $term->tid;
    }
    $parent_mlid = 0;
    foreach ($tags as $name => $data) {
      $term = $data['term'];
      $edit = array(
        'link_title' => "$name link",
        'link_path' => "taxonomy/term/{$term->tid}",
        'parent' => "$menu:{$parent_mlid}",
      );
      $this->drupalPost("admin/structure/menu/manage/$menu/add", $edit, t('Save'));
      $tags[$name]['link'] = db_query('SELECT * FROM {menu_links} WHERE link_title = :title AND link_path = :href', array(
        ':title' => $edit['link_title'],
        ':href' => $edit['link_path'],
      ))->fetchAssoc();
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
      $this->assertBreadcrumb($link['link_path'], $trail, $term->name, $tree);
      $this->assertRaw(check_plain($parent->title), 'Tagged node found.');

      // Additionally make sure that this link appears only once; i.e., the
      // untranslated menu links automatically generated from menu router items
      // ('taxonomy/term/%') should never be translated and appear in any menu
      // other than the breadcrumb trail.
      $elements = $this->xpath('//div[@id=:menu]/descendant::a[@href=:href]', array(
        ':menu' => 'block-system-navigation',
        ':href' => url($link['link_path']),
      ));
      $this->assertTrue(count($elements) == 1, "Link to {$link['link_path']} appears only once.");

      // Next iteration should expect this tag as parent link.
      // Note: Term name, not link name, due to taxonomy_term_page().
      $trail += array(
        $link['link_path'] => $term->name,
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
    $this->assertBreadcrumb('user', $trail, t('User account'));
    $this->assertBreadcrumb('user/' . $this->admin_user->uid, $trail, $this->admin_user->name);

    // Verify breadcrumb on user pages (without menu link) for registered users.
    $this->drupalLogin($this->admin_user);
    $trail = $home;
    $this->assertBreadcrumb('user', $trail, $this->admin_user->name);
    $this->assertBreadcrumb('user/' . $this->admin_user->uid, $trail, $this->admin_user->name);
    $trail += array(
      'user/' . $this->admin_user->uid => $this->admin_user->name,
    );
    $this->assertBreadcrumb('user/' . $this->admin_user->uid . '/edit', $trail, $this->admin_user->name);

    // Create a second user to verify breadcrumb on user pages again.
    $this->web_user = $this->drupalCreateUser(array(
      'administer users',
      'access user profiles',
    ));
    $this->drupalLogin($this->web_user);

    // Verify correct breadcrumb and page title on another user's account pages
    // (without menu link).
    $trail = $home;
    $this->assertBreadcrumb('user/' . $this->admin_user->uid, $trail, $this->admin_user->name);
    $trail += array(
      'user/' . $this->admin_user->uid => $this->admin_user->name,
    );
    $this->assertBreadcrumb('user/' . $this->admin_user->uid . '/edit', $trail, $this->admin_user->name);

    // Verify correct breadcrumb and page title when viewing own user account
    // pages (without menu link).
    $trail = $home;
    $this->assertBreadcrumb('user/' . $this->web_user->uid, $trail, $this->web_user->name);
    $trail += array(
      'user/' . $this->web_user->uid => $this->web_user->name,
    );
    $this->assertBreadcrumb('user/' . $this->web_user->uid . '/edit', $trail, $this->web_user->name);

    // Add a Navigation menu links for 'user' and $this->admin_user.
    // Although it may be faster to manage these links via low-level API
    // functions, there's a lot that can go wrong in doing so.
    $this->drupalLogin($this->admin_user);
    $edit = array(
      'link_title' => 'User',
      'link_path' => 'user',
    );
    $this->drupalPost("admin/structure/menu/manage/$menu/add", $edit, t('Save'));
    $link_user = db_query('SELECT * FROM {menu_links} WHERE link_title = :title AND link_path = :href', array(
      ':title' => $edit['link_title'],
      ':href' => $edit['link_path'],
    ))->fetchAssoc();

    $edit = array(
      'link_title' => $this->admin_user->name . ' link',
      'link_path' => 'user/' . $this->admin_user->uid,
    );
    $this->drupalPost("admin/structure/menu/manage/$menu/add", $edit, t('Save'));
    $link_admin_user = db_query('SELECT * FROM {menu_links} WHERE link_title = :title AND link_path = :href', array(
      ':title' => $edit['link_title'],
      ':href' => $edit['link_path'],
    ))->fetchAssoc();

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
    $this->assertBreadcrumb('user/' . $this->admin_user->uid, $trail, $link_admin_user['link_title'], $tree);

    $this->drupalLogin($this->admin_user);
    $trail += array(
      $link_admin_user['link_path'] => $link_admin_user['link_title'],
    );
    $this->assertBreadcrumb('user/' . $this->admin_user->uid . '/edit', $trail, $link_admin_user['link_title'], $tree, FALSE);

    // Move 'user/%' below 'user' and verify again.
    $edit = array(
      'parent' => "$menu:{$link_user['mlid']}",
    );
    $this->drupalPost("admin/structure/menu/item/{$link_admin_user['mlid']}/edit", $edit, t('Save'));

    $this->drupalLogout();
    $trail = $home;
    $tree = array(
      $link_user['link_path'] => $link_user['link_title'],
    );
    $this->assertBreadcrumb('user', $trail, $link_user['link_title'], $tree);
    $trail += array(
      $link_user['link_path'] => $link_user['link_title'],
    );
    $tree += array(
      $link_admin_user['link_path'] => $link_admin_user['link_title'],
    );
    $this->assertBreadcrumb('user/' . $this->admin_user->uid, $trail, $link_admin_user['link_title'], $tree);

    $this->drupalLogin($this->admin_user);
    $trail += array(
      $link_admin_user['link_path'] => $link_admin_user['link_title'],
    );
    $this->assertBreadcrumb('user/' . $this->admin_user->uid . '/edit', $trail, $link_admin_user['link_title'], $tree, FALSE);

    // Create an only slightly privileged user being able to access site reports
    // but not administration pages.
    $this->web_user = $this->drupalCreateUser(array(
      'access site reports',
    ));
    $this->drupalLogin($this->web_user);

    // Verify that we can access recent log entries, there is a corresponding
    // page title, and that the breadcrumb is empty (because the user is not
    // able to access "Administer", so the trail cannot recurse into it).
    $trail = array();
    $this->assertBreadcrumb('admin', $trail, t('Access denied'));
    $this->assertResponse(403);

    $trail = $home;
    $this->assertBreadcrumb('admin/reports', $trail, t('Reports'));
    $this->assertNoResponse(403);

    $this->assertBreadcrumb('admin/reports/dblog', $trail, t('Recent log messages'));
    $this->assertNoResponse(403);
  }
}
