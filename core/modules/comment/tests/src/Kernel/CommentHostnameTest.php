<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

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
  public function testGetDefaultHostname(): void {
    // Create a fake request to be used for testing.
    $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.1']);
    $request->setSession(new Session(new MockArraySessionStorage()));
    /** @var \Symfony\Component\HttpFoundation\RequestStack $stack */
    $stack = $this->container->get('request_stack');
    $stack->push($request);

    CommentType::create([
      'id' => 'foo',
      'label' => 'Foo',
      'target_entity_type_id' => 'entity_test',
    ])->save();

    // Check that the hostname is empty by default.
    $comment = Comment::create(['comment_type' => 'foo']);
    $this->assertEquals('', $comment->getHostname());

    \Drupal::configFactory()
      ->getEditable('comment.settings')
      ->set('log_ip_addresses', TRUE)
      ->save(TRUE);
    // Check that the hostname was set correctly.
    $comment = Comment::create(['comment_type' => 'foo']);
    $this->assertEquals('203.0.113.1', $comment->getHostname());
  }

}
