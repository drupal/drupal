<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Component\Utility\UrlHelper;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\system\Kernel\Token\TokenReplaceKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests comment token replacement.
 */
#[Group('comment')]
#[RunTestsInSeparateProcesses]
class CommentTokenReplaceTest extends TokenReplaceKernelTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'entity_test', 'filter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('comment');
    $this->installEntitySchema('entity_test');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig(['comment', 'filter']);
    $this->addDefaultCommentField('entity_test', 'entity_test', 'comment');
  }

  /**
   * Tests that the homepage token handles both NULL and set values.
   */
  public function testHomepageToken(): void {
    $account = $this->createUser();

    // Create a host entity.
    $entity = EntityTest::create(['name' => 'Test entity']);
    $entity->save();

    // Create a comment without setting a homepage.
    $comment = Comment::create([
      'subject' => 'Test comment',
      'uid' => $account->id(),
      'entity_type' => 'entity_test',
      'field_name' => 'comment',
      'entity_id' => $entity->id(),
      'comment_type' => 'comment',
      'status' => 1,
      'comment_body' => [
        'value' => 'This is the comment body.',
        'format' => 'plain_text',
      ],
    ]);
    $comment->save();

    // Verify that getHomepage() returns NULL when no homepage is set.
    $this->assertNull($comment->getHomepage());

    // Replace the homepage token. This should not produce any errors and
    // should return an empty string when the homepage is NULL.
    $output = $this->tokenService->replace('[comment:homepage]', ['comment' => $comment], ['langcode' => $this->interfaceLanguage->getId()]);
    $this->assertSame('', $output, 'Homepage token with NULL value returns empty string.');

    // Now set a homepage and verify the token returns the sanitized URL.
    $comment->setHomepage('http://example.org/');
    $comment->save();

    $output = $this->tokenService->replace('[comment:homepage]', ['comment' => $comment], ['langcode' => $this->interfaceLanguage->getId()]);
    $this->assertSame(UrlHelper::stripDangerousProtocols('http://example.org/'), $output, 'Homepage token returns sanitized URL.');
  }

}
