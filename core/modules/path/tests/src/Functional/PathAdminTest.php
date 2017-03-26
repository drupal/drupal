<?php

namespace Drupal\Tests\path\Functional;

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
  public static $modules = ['path'];

  protected function setUp() {
    parent::setUp();

    // Create test user and log in.
    $web_user = $this->drupalCreateUser(['create page content', 'edit own page content', 'administer url aliases', 'create url aliases']);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the filtering aspect of the Path UI.
   */
  public function testPathFiltering() {
    // Create test nodes.
    $node1 = $this->drupalCreateNode();
    $node2 = $this->drupalCreateNode();
    $node3 = $this->drupalCreateNode();

    // Create aliases.
    $alias1 = '/' . $this->randomMachineName(8);
    $edit = [
      'source' => '/node/' . $node1->id(),
      'alias' => $alias1,
    ];
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    $alias2 = '/' . $this->randomMachineName(8);
    $edit = [
      'source' => '/node/' . $node2->id(),
      'alias' => $alias2,
    ];
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    $alias3 = '/' . $this->randomMachineName(4) . '/' . $this->randomMachineName(4);
    $edit = [
      'source' => '/node/' . $node3->id(),
      'alias' => $alias3,
    ];
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    // Filter by the first alias.
    $edit = [
      'filter' => $alias1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Filter'));
    $this->assertLinkByHref($alias1);
    $this->assertNoLinkByHref($alias2);
    $this->assertNoLinkByHref($alias3);

    // Filter by the second alias.
    $edit = [
      'filter' => $alias2,
    ];
    $this->drupalPostForm(NULL, $edit, t('Filter'));
    $this->assertNoLinkByHref($alias1);
    $this->assertLinkByHref($alias2);
    $this->assertNoLinkByHref($alias3);

    // Filter by the third alias which has a slash.
    $edit = [
      'filter' => $alias3,
    ];
    $this->drupalPostForm(NULL, $edit, t('Filter'));
    $this->assertNoLinkByHref($alias1);
    $this->assertNoLinkByHref($alias2);
    $this->assertLinkByHref($alias3);

    // Filter by a random string with a different length.
    $edit = [
      'filter' => $this->randomMachineName(10),
    ];
    $this->drupalPostForm(NULL, $edit, t('Filter'));
    $this->assertNoLinkByHref($alias1);
    $this->assertNoLinkByHref($alias2);

    // Reset the filter.
    $edit = [];
    $this->drupalPostForm(NULL, $edit, t('Reset'));
    $this->assertLinkByHref($alias1);
    $this->assertLinkByHref($alias2);
  }

}
