<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Unit\Entity;

use Drupal\comment\CommentStatisticsInterface;
use Drupal\comment\CommentStorageInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests comment acquires and releases the right lock.
 */
#[Group('comment')]
class CommentLockTest extends UnitTestCase {

  /**
   * Tests the lock behavior.
   */
  public function testLocks(): void {
    $container = new ContainerBuilder();
    $container->set('module_handler', $this->createStub(ModuleHandlerInterface::class));
    $container->set('current_user', $this->createStub(AccountInterface::class));
    $container->set('cache.test', $this->createStub(CacheBackendInterface::class));
    $container->set('comment.statistics', $this->createStub(CommentStatisticsInterface::class));
    $request_stack = new RequestStack();
    $request_stack->push(Request::create('/'));
    $container->set('request_stack', $request_stack);
    $container->setParameter('cache_bins', ['cache.test' => 'test']);
    $lock = $this->createMock('Drupal\Core\Lock\LockBackendInterface');
    $cid = 2;
    $lock_name = "comment:$cid:.00/";
    $lock->expects($this->once())
      ->method('acquire')
      ->with($lock_name, 30)
      ->willReturn(TRUE);
    $lock->expects($this->once())
      ->method('release')
      ->with($lock_name);
    $lock->expects($this->exactly(2))
      ->method($this->anything());
    $container->set('lock', $lock);

    $container->set('cache_tags.invalidator', $this->createStub(CacheTagsInvalidator::class));

    \Drupal::setContainer($container);
    $methods = get_class_methods('Drupal\comment\Entity\Comment');
    unset($methods[array_search('preSave', $methods)]);
    unset($methods[array_search('postSave', $methods)]);
    $methods[] = 'invalidateTagsOnSave';
    $comment = $this->getMockBuilder('Drupal\comment\Entity\Comment')
      ->disableOriginalConstructor()
      ->onlyMethods($methods)
      ->getMock();
    $comment->expects($this->once())
      ->method('isNew')
      ->willReturn(TRUE);
    $comment->expects($this->once())
      ->method('hasParentComment')
      ->willReturn(TRUE);
    $comment->expects($this->once())
      ->method('getParentComment')
      ->willReturn($comment);
    $comment->expects($this->once())
      ->method('getCommentedEntityId')
      ->willReturn($cid);
    $comment
      ->method('getThread')
      ->willReturn('');

    $anon_user = $this->createStub(AccountInterface::class);
    $anon_user
      ->method('isAnonymous')
      ->willReturn(TRUE);
    $comment
      ->method('getOwner')
      ->willReturn($anon_user);

    $parent_entity = $this->createMock('\Drupal\Core\Entity\ContentEntityInterface');
    $parent_entity->expects($this->atLeastOnce())
      ->method('getCacheTagsToInvalidate')
      ->willReturn(['node:1']);
    $comment->expects($this->once())
      ->method('getCommentedEntity')
      ->willReturn($parent_entity);

    $comment
      ->method('getEntityType')
      ->willReturn($this->createStub(EntityTypeInterface::class));
    $storage = $this->createStub(CommentStorageInterface::class);

    // preSave() should acquire the lock. (This is what's really being tested.)
    $comment->preSave($storage);
    // Release the acquired lock before exiting the test.
    $comment->postSave($storage);
  }

}
