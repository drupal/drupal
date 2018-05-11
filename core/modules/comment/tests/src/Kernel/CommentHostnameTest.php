<?php

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the hostname base field.
 *
 * @coversDefaultClass \Drupal\comment\Entity\Comment
 *
 * @group comment
 */
class CommentHostnameTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'entity_test', 'user'];

  /**
   * Tests hostname default value callback.
   *
   * @covers ::getDefaultHostname
   */
  public function testGetDefaultHostname() {
    // Create a fake request to be used for testing.
    $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.1']);
    /** @var \Symfony\Component\HttpFoundation\RequestStack $stack */
    $stack = $this->container->get('request_stack');
    $stack->push($request);

    CommentType::create([
      'id' => 'foo',
      'target_entity_type_id' => 'entity_test',
    ])->save();
    $comment = Comment::create(['comment_type' => 'foo']);

    // Check that the hostname was set correctly.
    $this->assertEquals('203.0.113.1', $comment->getHostname());
  }

}
