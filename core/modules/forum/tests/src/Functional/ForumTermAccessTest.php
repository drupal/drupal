<?php

namespace Drupal\Tests\forum\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Tests forum taxonomy terms for access.
 *
 * @group forum
 */
class ForumTermAccessTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'forum',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Creates some users and creates a public forum and an unpublished forum.
   *
   * Adds both published and unpublished forums.
   * Tests to ensure publish/unpublished forums access is respected.
   */
  public function testForumTermAccess(): void {
    $assert_session = $this->assertSession();
    // Create some users.
    $public_user = $this->drupalCreateUser(['access content']);
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer forums',
      'administer taxonomy',
      'access taxonomy overview',
    ]);

    $this->drupalLogin($admin_user);
    // The vocabulary for forums.
    $vid = $this->config('forum.settings')->get('vocabulary');
    // Create an unpublished forum.
    $unpublished_forum_name = $this->randomMachineName(8);
    $unpublished_forum = Term::create([
      'vid' => $vid,
      'name' => $unpublished_forum_name,
      'status' => 0,
    ]);
    $unpublished_forum->save();

    // Create a new published forum.
    $published_forum_name = $this->randomMachineName(8);
    $published_forum = Term::create([
      'vid' => $vid,
      'name' => $published_forum_name,
      'status' => 1,
    ]);
    $published_forum->save();

    // Test for admin user.
    // Go to the Forum index page.
    $this->drupalGet('forum');
    // The unpublished forum should be in this page for an admin user.
    $assert_session->pageTextContains($unpublished_forum_name);
    // Go to the unpublished forum page.
    $this->drupalGet('forum/' . $unpublished_forum->id());
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains($unpublished_forum_name);

    // Test for public user.
    $this->drupalLogin($public_user);
    // Go to the Forum index page.
    $this->drupalGet('forum');
    // The published forum should be in this page.
    $assert_session->pageTextContains($published_forum_name);
    // The unpublished forum should not be in this page.
    $assert_session->pageTextNotContains($unpublished_forum_name);
    // Go to the unpublished forum page.
    $this->drupalGet('forum/' . $unpublished_forum->id());
    // Public should not be able to access the unpublished forum.
    $assert_session->statusCodeEquals(403);
    $assert_session->pageTextNotContains($unpublished_forum_name);
  }

}
