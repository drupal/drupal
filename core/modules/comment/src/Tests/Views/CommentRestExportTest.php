<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Views\CommentRestExportTest.
 */

namespace Drupal\comment\Tests\Views;

use Drupal\Component\Serialization\Json;

/**
 * Tests a comment rest export view.
 *
 * @group comment
 */
class CommentRestExportTest extends CommentTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_comment_rest'];

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'comment', 'comment_test_views', 'rest', 'hal'];

  protected function setUp() {
    parent::setUp();
    // Add another anonymous comment.
    $comment = array(
      'uid' => 0,
      'entity_id' => $this->nodeUserCommented->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'subject' => 'A lot, apparently',
      'cid' => '',
      'pid' => $this->comment->id(),
      'mail' => 'someone@example.com',
      'name' => 'bobby tables',
      'hostname' => 'public.example.com',
    );
    $this->comment = entity_create('comment', $comment);
    $this->comment->save();

    $user = $this->drupalCreateUser(['access comments']);
    $this->drupalLogin($user);
  }


  /**
   * Test comment row.
   */
  public function testCommentRestExport() {
    $this->drupalGetWithFormat(sprintf('node/%d/comments', $this->nodeUserCommented->id()), 'hal_json');
    $this->assertResponse(200);
    $contents = Json::decode($this->getRawContent());
    $this->assertEqual($contents[0]['subject'], 'How much wood would a woodchuck chuck');
    $this->assertEqual($contents[1]['subject'], 'A lot, apparently');
    $this->assertEqual(count($contents), 2);

    // Ensure field-level access is respected - user shouldn't be able to see
    // mail or hostname fields.
    $this->assertNoText('someone@example.com');
    $this->assertNoText('public.example.com');
  }

}
