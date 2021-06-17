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
  protected static $modules = ['path'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    // Create test user and log in.
    $web_user = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
      'administer url aliases',
      'create url aliases',
    ]);
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
      'path[0][value]' => '/node/' . $node1->id(),
      'alias[0][value]' => $alias1,
    ];
    $this->drupalGet('admin/config/search/path/add');
    $this->submitForm($edit, 'Save');

    $alias2 = '/' . $this->randomMachineName(8);
    $edit = [
      'path[0][value]' => '/node/' . $node2->id(),
      'alias[0][value]' => $alias2,
    ];
    $this->drupalGet('admin/config/search/path/add');
    $this->submitForm($edit, 'Save');

    $alias3 = '/' . $this->randomMachineName(4) . '/' . $this->randomMachineName(4);
    $edit = [
      'path[0][value]' => '/node/' . $node3->id(),
      'alias[0][value]' => $alias3,
    ];
    $this->drupalGet('admin/config/search/path/add');
    $this->submitForm($edit, 'Save');

    // Filter by the first alias.
    $edit = [
      'filter' => $alias1,
    ];
    $this->submitForm($edit, 'Filter');
    $this->assertSession()->linkByHrefExists($alias1);
    $this->assertSession()->linkByHrefNotExists($alias2);
    $this->assertSession()->linkByHrefNotExists($alias3);

    // Filter by the second alias.
    $edit = [
      'filter' => $alias2,
    ];
    $this->submitForm($edit, 'Filter');
    $this->assertSession()->linkByHrefNotExists($alias1);
    $this->assertSession()->linkByHrefExists($alias2);
    $this->assertSession()->linkByHrefNotExists($alias3);

    // Filter by the third alias which has a slash.
    $edit = [
      'filter' => $alias3,
    ];
    $this->submitForm($edit, 'Filter');
    $this->assertSession()->linkByHrefNotExists($alias1);
    $this->assertSession()->linkByHrefNotExists($alias2);
    $this->assertSession()->linkByHrefExists($alias3);

    // Filter by a random string with a different length.
    $edit = [
      'filter' => $this->randomMachineName(10),
    ];
    $this->submitForm($edit, 'Filter');
    $this->assertSession()->linkByHrefNotExists($alias1);
    $this->assertSession()->linkByHrefNotExists($alias2);

    // Reset the filter.
    $edit = [];
    $this->submitForm($edit, 'Reset');
    $this->assertSession()->linkByHrefExists($alias1);
    $this->assertSession()->linkByHrefExists($alias2);
  }

}
