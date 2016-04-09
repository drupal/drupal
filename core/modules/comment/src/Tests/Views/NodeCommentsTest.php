<?php

namespace Drupal\comment\Tests\Views;

/**
 * Tests comments on nodes.
 *
 * @group comment
 */
class NodeCommentsTest extends CommentTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['history'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_new_comments'];

  /**
   * Test the new comments field plugin.
   */
  public function testNewComments() {
    $this->drupalGet('test-new-comments');
    $this->assertResponse(200);
    $new_comments = $this->cssSelect(".views-field-new-comments a:contains('1')");
    $this->assertEqual(count($new_comments), 1, 'Found the number of new comments for a certain node.');
  }

}
