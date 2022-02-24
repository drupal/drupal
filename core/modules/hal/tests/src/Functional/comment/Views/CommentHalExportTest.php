<?php

namespace Drupal\Tests\hal\Functional\comment\Views;

use Drupal\Component\Serialization\Json;
use Drupal\comment\Entity\Comment;
use Drupal\Tests\comment\Functional\Views\CommentTestBase;

/**
 * Tests a comment rest export view.
 *
 * @group hal
 * @group legacy
 */
class CommentHalExportTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_comment_hal'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'comment',
    'hal_test_views',
    'rest',
    'hal',
  ];

  protected function setUp($import_test_views = TRUE, $modules = ['hal_test_views']): void {
    parent::setUp($import_test_views, $modules);
    // Add another anonymous comment.
    $comment = [
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
    ];
    $this->comment = Comment::create($comment);
    $this->comment->save();

    $user = $this->drupalCreateUser(['access comments']);
    $this->drupalLogin($user);
  }

  /**
   * Tests comment row.
   */
  public function testCommentRestExport() {
    $this->drupalGet(sprintf('node/%d/comments', $this->nodeUserCommented->id()), ['query' => ['_format' => 'hal_json']]);
    $this->assertSession()->statusCodeEquals(200);
    $contents = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertEquals('How much wood would a woodchuck chuck', $contents[0]['subject']);
    $this->assertEquals('A lot, apparently', $contents[1]['subject']);
    $this->assertCount(2, $contents);

    // Ensure field-level access is respected - user shouldn't be able to see
    // mail or hostname fields.
    $this->assertSession()->responseNotContains('someone@example.com');
    $this->assertSession()->responseNotContains('public.example.com');
  }

}
