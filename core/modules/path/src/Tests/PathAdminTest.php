<?php

/**
 * @file
 * Contains \Drupal\path\Tests\PathAdminTest.
 */

namespace Drupal\path\Tests;

/**
 * Tests the Path admin UI.
 *
 * @group path
 */
class PathAdminTest extends PathTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('path');

  function setUp() {
    parent::setUp();

    // Create test user and login.
    $web_user = $this->drupalCreateUser(array('create page content', 'edit own page content', 'administer url aliases', 'create url aliases'));
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the filtering aspect of the Path UI.
   */
  public function testPathFiltering() {
    // Create test nodes.
    $node1 = $this->drupalCreateNode();
    $node2 = $this->drupalCreateNode();

    // Create aliases.
    $alias1 = $this->randomMachineName(8);
    $edit = array(
      'source' => 'node/' . $node1->id(),
      'alias' => $alias1,
    );
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    $alias2 = $this->randomMachineName(8);
    $edit = array(
      'source' => 'node/' . $node2->id(),
      'alias' => $alias2,
    );
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    // Filter by the first alias.
    $edit = array(
      'filter' => $alias1,
    );
    $this->drupalPostForm(NULL, $edit, t('Filter'));
    $this->assertLinkByHref($alias1);
    $this->assertNoLinkByHref($alias2);

    // Filter by the second alias.
    $edit = array(
      'filter' => $alias2,
    );
    $this->drupalPostForm(NULL, $edit, t('Filter'));
    $this->assertNoLinkByHref($alias1);
    $this->assertLinkByHref($alias2);

    // Filter by a random string with a different length.
    $edit = array(
      'filter' => $this->randomMachineName(10),
    );
    $this->drupalPostForm(NULL, $edit, t('Filter'));
    $this->assertNoLinkByHref($alias1);
    $this->assertNoLinkByHref($alias2);

    // Reset the filter.
    $edit = array();
    $this->drupalPostForm(NULL, $edit, t('Reset'));
    $this->assertLinkByHref($alias1);
    $this->assertLinkByHref($alias2);
  }

}
