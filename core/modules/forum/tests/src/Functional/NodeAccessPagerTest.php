<?php

namespace Drupal\Tests\forum\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests access controlled node views have the right amount of comment pages.
 *
 * @group form
 */
class NodeAccessPagerTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node_access_test', 'forum'];

  /**
   * A user account to use for the test.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    node_access_rebuild();
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->webUser = $this->drupalCreateUser([
      'access content',
      'node test view',
    ]);
  }

  /**
   * Tests the forum node pager for nodes with multiple grants per realm.
   */
  public function testForumPager() {
    // Look up the forums vocabulary ID.
    $vid = $this->config('forum.settings')->get('vocabulary');
    $this->assertNotEmpty($vid, 'Forum navigation vocabulary ID is set.');

    // Look up the general discussion term.
    $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, 0, 1);
    $tid = reset($tree)->tid;
    $this->assertNotEmpty($tid, 'General discussion term is found in the forum vocabulary.');

    // Create 30 nodes.
    for ($i = 0; $i < 30; $i++) {
      $this->drupalCreateNode([
        'nid' => NULL,
        'type' => 'forum',
        'taxonomy_forums' => [
          ['target_id' => $tid],
        ],
      ]);
    }

    // View the general discussion forum page. With the default 25 nodes per
    // page there should be two pages for 30 nodes, no more.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('forum/' . $tid);
    $this->assertSession()->responseContains('page=1');
    $this->assertSession()->responseNotContains('page=2');
  }

}
