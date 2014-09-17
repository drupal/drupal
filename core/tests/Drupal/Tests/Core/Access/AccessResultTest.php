<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\AccessResultTest.
 */

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Access\AccessResult
 * @group Access
 */
class AccessResultTest extends UnitTestCase {

  protected function assertDefaultCacheability(AccessResult $access) {
    $this->assertTrue($access->isCacheable());
    $this->assertSame([], $access->getCacheKeys());
    $this->assertSame([], $access->getCacheTags());
    $this->assertSame('default', $access->getCacheBin());
    $this->assertSame(Cache::PERMANENT, $access->getCacheMaxAge());
  }

  /**
   * Tests the construction of an AccessResult object.
   *
   * @covers ::__construct
   * @covers ::create
   * @covers ::getCacheBin
   */
  public function testConstruction() {
    $verify = function (AccessResult $access) {
      $this->assertFalse($access->isAllowed());
      $this->assertFalse($access->isForbidden());
      $this->assertDefaultCacheability($access);
    };

    // Verify the object when using the constructor.
    $a = new AccessResult();
    $verify($a);

    // Verify the object when using the ::create() convenience method.
    $b = AccessResult::create();
    $verify($b);

    $this->assertEquals($a, $b);
  }

  /**
   * @covers ::allow
   * @covers ::allowed
   * @covers ::isAllowed
   * @covers ::isForbidden
   */
  public function testAccessAllowed() {
    $verify = function (AccessResult $access) {
      $this->assertTrue($access->isAllowed());
      $this->assertFalse($access->isForbidden());
      $this->assertDefaultCacheability($access);
    };

    // Verify the object when using the ::allow() instance method.
    $a = AccessResult::create()->allow();
    $verify($a);

    // Verify the object when using the ::allowed() convenience static method.
    $b = AccessResult::allowed();
    $verify($b);

    $this->assertEquals($a, $b);
  }

  /**
   * @covers ::forbid
   * @covers ::forbidden
   * @covers ::isAllowed
   * @covers ::isForbidden
   */
  public function testAccessForbidden() {
    $verify = function (AccessResult $access) {
      $this->assertFalse($access->isAllowed());
      $this->assertTrue($access->isForbidden());
      $this->assertDefaultCacheability($access);
    };

    // Verify the object when using the ::forbid() instance method.
    $a = AccessResult::create()->forbid();
    $verify($a);

    // Verify the object when using the ::forbidden() convenience static method.
    $b = AccessResult::forbidden();
    $verify($b);

    $this->assertEquals($a, $b);
  }

  /**
   * @covers ::reset
   * @covers ::isAllowed
   * @covers ::isForbidden
   */
  public function testAccessReset() {
    $verify = function (AccessResult $access) {
      $this->assertFalse($access->isAllowed());
      $this->assertFalse($access->isForbidden());
      $this->assertDefaultCacheability($access);
    };

    $a = AccessResult::allowed()->resetAccess();
    $verify($a);

    $b = AccessResult::forbidden()->resetAccess();
    $verify($b);

    $this->assertEquals($a, $b);
  }

  /**
   * @covers ::allowIf
   * @covers ::allowedIf
   * @covers ::isAllowed
   * @covers ::isForbidden
   */
  public function testAccessConditionallyAllowed() {
    $verify = function (AccessResult $access, $allowed, $forbidden = FALSE) {
      $this->assertSame($allowed, $access->isAllowed());
      $this->assertSame($forbidden, $access->isForbidden());
      $this->assertDefaultCacheability($access);
    };

    // Verify the object when using the ::allowIf() instance method.
    $a1 = AccessResult::create()->allowIf(TRUE);
    $verify($a1, TRUE);
    $a2 = AccessResult::create()->allowIf(FALSE);
    $verify($a2, FALSE);

    $b1 = AccessResult::allowedIf(TRUE);
    $verify($b1, TRUE);
    $b2 = AccessResult::allowedIf(FALSE);
    $verify($b2, FALSE);

    $this->assertEquals($a1, $b1);
    $this->assertEquals($a2, $b2);

    // Verify that ::allowIf() does not overwrite an existing value when the
    // condition does not evaluate to TRUE.
    $a1 = AccessResult::forbidden()->allowIf(TRUE);
    $verify($a1, TRUE);
    $a2 = AccessResult::forbidden()->allowIf(FALSE);
    $verify($a2, FALSE, TRUE);
  }

  /**
   * @covers ::forbidIf
   * @covers ::forbiddenIf
   * @covers ::isAllowed
   * @covers ::isForbidden
   */
  public function testAccessConditionallyForbidden() {
    $verify = function (AccessResult $access, $forbidden, $allowed = FALSE) {
      $this->assertSame($allowed, $access->isAllowed());
      $this->assertSame($forbidden, $access->isForbidden());
      $this->assertDefaultCacheability($access);
    };

    // Verify the object when using the ::allowIf() instance method.
    $a1 = AccessResult::create()->forbidIf(TRUE);
    $verify($a1, TRUE);
    $a2 = AccessResult::create()->forbidIf(FALSE);
    $verify($a2, FALSE);

    $b1 = AccessResult::forbiddenIf(TRUE);
    $verify($b1, TRUE);
    $b2 = AccessResult::forbiddenIf(FALSE);
    $verify($b2, FALSE);

    $this->assertEquals($a1, $b1);
    $this->assertEquals($a2, $b2);

    // Verify that ::forbidIf() does not overwrite an existing value when the
    // condition does not evaluate to TRUE.
    $a1 = AccessResult::allowed()->forbidIf(TRUE);
    $verify($a1, TRUE);
    $a2 = AccessResult::allowed()->forbidIf(FALSE);
    $verify($a2, FALSE, TRUE);
  }

  /**
   * @covers ::andIf
   */
  public function testAndIf() {
    $no_opinion = AccessResult::create();
    $allowed = AccessResult::allowed();
    $forbidden = AccessResult::forbidden();
    $unused_access_result_due_to_lazy_evaluation = $this->getMock('\Drupal\Core\Access\AccessResultInterface');
    $unused_access_result_due_to_lazy_evaluation->expects($this->never())
      ->method($this->anything());

    // ALLOW && ALLOW === ALLOW.
    $access = clone $allowed;
    $access->andIf($allowed);
    $this->assertTrue($access->isAllowed());
    $this->assertFalse($access->isForbidden());
    $this->assertDefaultCacheability($access);

    // ALLOW && DENY === DENY.
    $access = clone $allowed;
    $access->andIf($no_opinion);
    $this->assertFalse($access->isAllowed());
    $this->assertFalse($access->isForbidden());
    $this->assertDefaultCacheability($access);

    // ALLOW && KILL === KILL.
    $access = clone $allowed;
    $access->andIf($forbidden);
    $this->assertFalse($access->isAllowed());
    $this->assertTrue($access->isForbidden());
    $this->assertDefaultCacheability($access);

    // DENY && * === DENY.
    $access = clone $no_opinion;
    $access->andIf($unused_access_result_due_to_lazy_evaluation);
    $this->assertFalse($access->isAllowed());
    $this->assertFalse($access->isForbidden());
    $this->assertDefaultCacheability($access);

    // KILL && * === KILL.
    $access = clone $forbidden;
    $access->andIf($unused_access_result_due_to_lazy_evaluation);
    $this->assertFalse($access->isAllowed());
    $this->assertTrue($access->isForbidden());
    $this->assertDefaultCacheability($access);
  }

  /**
   * @covers ::orIf
   */
  public function testOrIf() {
    $no_opinion = AccessResult::create();
    $allowed = AccessResult::allowed();
    $forbidden = AccessResult::forbidden();
    $unused_access_result_due_to_lazy_evaluation = $this->getMock('\Drupal\Core\Access\AccessResultInterface');
    $unused_access_result_due_to_lazy_evaluation->expects($this->never())
      ->method($this->anything());

    // ALLOW || ALLOW === ALLOW.
    $access = clone $allowed;
    $access->orIf($allowed);
    $this->assertTrue($access->isAllowed());
    $this->assertFalse($access->isForbidden());
    $this->assertDefaultCacheability($access);

    // ALLOW || DENY === ALLOW.
    $access = clone $allowed;
    $access->orIf($no_opinion);
    $this->assertTrue($access->isAllowed());
    $this->assertFalse($access->isForbidden());
    $this->assertDefaultCacheability($access);

    // ALLOW || KILL === KILL.
    $access = clone $allowed;
    $access->orIf($forbidden);
    $this->assertFalse($access->isAllowed());
    $this->assertTrue($access->isForbidden());
    $this->assertDefaultCacheability($access);

    // DENY || DENY === DENY.
    $access = clone $no_opinion;
    $access->orIf($no_opinion);
    $this->assertFalse($access->isAllowed());
    $this->assertFalse($access->isForbidden());
    $this->assertDefaultCacheability($access);

    // DENY || ALLOW === ALLOW.
    $access = clone $no_opinion;
    $access->orIf($allowed);
    $this->assertTrue($access->isAllowed());
    $this->assertFalse($access->isForbidden());
    $this->assertDefaultCacheability($access);

    // DENY || KILL === KILL.
    $access = clone $no_opinion;
    $access->orIf($forbidden);
    $this->assertFalse($access->isAllowed());
    $this->assertTrue($access->isForbidden());
    $this->assertDefaultCacheability($access);

    // KILL || * === KILL.
    $access = clone $forbidden;
    $access->orIf($unused_access_result_due_to_lazy_evaluation);
    $this->assertFalse($access->isAllowed());
    $this->assertTrue($access->isForbidden());
    $this->assertDefaultCacheability($access);
  }

  /**
   * @covers ::setCacheable
   * @covers ::isCacheable
   */
  public function testCacheable() {
    $this->assertTrue(AccessResult::create()->isCacheable());
    $this->assertTrue(AccessResult::create()->setCacheable(TRUE)->isCacheable());
    $this->assertFalse(AccessResult::create()->setCacheable(FALSE)->isCacheable());
  }

  /**
   * @covers ::setCacheMaxAge
   * @covers ::getCacheMaxAge
   */
  public function testCacheMaxAge() {
    $this->assertSame(Cache::PERMANENT, AccessResult::create()->getCacheMaxAge());
    $this->assertSame(1337, AccessResult::create()->setCacheMaxAge(1337)->getCacheMaxAge());
  }

  /**
   * @covers ::addCacheContexts
   * @covers ::resetCacheContexts
   * @covers ::getCacheKeys
   * @covers ::cachePerRole
   * @covers ::cachePerUser
   * @covers ::allowIfHasPermission
   * @covers ::allowedIfHasPermission
   */
  public function testCacheContexts() {
    $verify = function (AccessResult $access, array $contexts) {
      $this->assertFalse($access->isAllowed());
      $this->assertFalse($access->isForbidden());
      $this->assertTrue($access->isCacheable());
      $this->assertSame('default', $access->getCacheBin());
      $this->assertSame(Cache::PERMANENT, $access->getCacheMaxAge());
      $this->assertSame($contexts, $access->getCacheKeys());
      $this->assertSame([], $access->getCacheTags());
    };

    $access = AccessResult::create()->addCacheContexts(['cache_context.foo']);
    $verify($access, ['cache_context.foo']);
    // Verify resetting works.
    $access->resetCacheContexts();
    $verify($access, []);
    // Verify idempotency.
    $access->addCacheContexts(['cache_context.foo'])
      ->addCacheContexts(['cache_context.foo']);
    $verify($access, ['cache_context.foo']);
    // Verify same values in different call order yields the same result.
    $access->resetCacheContexts()
      ->addCacheContexts(['cache_context.foo'])
      ->addCacheContexts(['cache_context.bar']);
    $verify($access, ['cache_context.bar', 'cache_context.foo']);
    $access->resetCacheContexts()
      ->addCacheContexts(['cache_context.bar'])
      ->addCacheContexts(['cache_context.foo']);
    $verify($access, ['cache_context.bar', 'cache_context.foo']);

    // ::cachePerRole() convenience method.
    $contexts = array('cache_context.user.roles');
    $a = AccessResult::create()->addCacheContexts($contexts);
    $verify($a, $contexts);
    $b = AccessResult::create()->cachePerRole();
    $verify($b, $contexts);
    $this->assertEquals($a, $b);

    // ::cachePerUser() convenience method.
    $contexts = array('cache_context.user');
    $a = AccessResult::create()->addCacheContexts($contexts);
    $verify($a, $contexts);
    $b = AccessResult::create()->cachePerUser();
    $verify($b, $contexts);
    $this->assertEquals($a, $b);

    // Both.
    $contexts = array('cache_context.user', 'cache_context.user.roles');
    $a = AccessResult::create()->addCacheContexts($contexts);
    $verify($a, $contexts);
    $b = AccessResult::create()->cachePerRole()->cachePerUser();
    $verify($b, $contexts);
    $c = AccessResult::create()->cachePerUser()->cachePerRole();
    $verify($c, $contexts);
    $this->assertEquals($a, $b);
    $this->assertEquals($a, $c);

    // ::allowIfHasPermission and ::allowedIfHasPermission convenience methods.
    $account = $this->getMock('\Drupal\Core\Session\AccountInterface');
    $account->expects($this->any())
      ->method('hasPermission')
      ->with('may herd llamas')
      ->will($this->returnValue(FALSE));
    $contexts = array('cache_context.user.roles');

    // Verify the object when using the ::allowIfHasPermission() convenience
    // instance method.
    $a = AccessResult::create()->allowIfHasPermission($account, 'may herd llamas');
    $verify($a, $contexts);

    // Verify the object when using the ::allowedIfHasPermission() convenience
    // static method.
    $b = AccessResult::allowedIfHasPermission($account, 'may herd llamas');
    $verify($b, $contexts);

    $this->assertEquals($a, $b);
  }

  /**
   * @covers ::addCacheTags
   * @covers ::resetCacheTags
   * @covers ::getCacheTags
   * @covers ::cacheUntilEntityChanges
   */
  public function testCacheTags() {
    $verify = function (AccessResult $access, array $tags) {
      $this->assertFalse($access->isAllowed());
      $this->assertFalse($access->isForbidden());
      $this->assertTrue($access->isCacheable());
      $this->assertSame('default', $access->getCacheBin());
      $this->assertSame(Cache::PERMANENT, $access->getCacheMaxAge());
      $this->assertSame([], $access->getCacheKeys());
      $this->assertSame($tags, $access->getCacheTags());
    };

    $access = AccessResult::create()->addCacheTags(['foo' => ['bar']]);
    $verify($access, ['foo' => ['bar' => 'bar']]);
    // Verify resetting works.
    $access->resetCacheTags();
    $verify($access, []);
    // Verify idempotency.
    $access->addCacheTags(['foo' => ['bar']])
      ->addCacheTags(['foo' => ['bar']]);
    $verify($access, ['foo' => ['bar' => 'bar']]);
    // Verify same values in different call order yields the same result.
    $access->resetCacheTags()
      ->addCacheTags(['bar' => ['baz']])
      ->addCacheTags(['bar' => ['qux']])
      ->addCacheTags(['foo' => ['bar']])
      ->addCacheTags(['foo' => ['baz']]);
    $verify($access, ['bar' => ['baz' => 'baz', 'qux' => 'qux'], 'foo' => ['bar' => 'bar', 'baz' => 'baz']]);
    $access->resetCacheTags()
      ->addCacheTags(['foo' => ['bar']])
      ->addCacheTags(['bar' => ['qux']])
      ->addCacheTags(['foo' => ['baz']])
      ->addCacheTags(['bar' => ['baz']]);
    $verify($access, ['bar' => ['baz' => 'baz', 'qux' => 'qux'], 'foo' => ['bar' => 'bar', 'baz' => 'baz']]);
    // Verify tags with nested arrays and without.
    $access->resetCacheTags()
      // Array.
      ->addCacheTags(['foo' => ['bar']])
      // String.
      ->addCacheTags(['bar' => 'baz'])
      // Boolean.
      ->addCacheTags(['qux' => TRUE]);
    $verify($access, ['bar' => 'baz', 'foo' => ['bar' => 'bar'], 'qux' => TRUE]);

    // ::cacheUntilEntityChanges() convenience method.
    $node = $this->getMock('\Drupal\node\NodeInterface');
    $node->expects($this->any())
      ->method('getCacheTag')
      ->will($this->returnValue(array('node' => array(20011988))));
    $tags = array('node' => array(20011988 => 20011988));
    $a = AccessResult::create()->addCacheTags($tags);
    $verify($a, $tags);
    $b = AccessResult::create()->cacheUntilEntityChanges($node);
    $verify($b, $tags);
    $this->assertEquals($a, $b);
  }

  /**
   * @covers ::andIf
   * @covers ::orIf
   * @covers ::mergeCacheabilityMetadata
   */
  public function testCacheabilityMerging() {
    $access_without_cacheability = $this->getMock('\Drupal\Core\Access\AccessResultInterface');
    $access_without_cacheability->expects($this->exactly(2))
      ->method('isAllowed')
      ->willReturn(TRUE);
    $access_without_cacheability->expects($this->exactly(2))
      ->method('isForbidden')
      ->willReturn(FALSE);
    $access_without_cacheability->expects($this->once())
      ->method('andIf')
      ->will($this->returnSelf());

    // andIf(); 1st has defaults, 2nd has custom tags, contexts and max-age.
    $access = AccessResult::allowed()
      ->andIf(AccessResult::allowed()->setCacheMaxAge(1500)->cachePerRole()->addCacheTags(['node' => [20011988]]));
    $this->assertTrue($access->isAllowed());
    $this->assertFalse($access->isForbidden());
    $this->assertTrue($access->isCacheable());
    $this->assertSame(['cache_context.user.roles'], $access->getCacheKeys());
    $this->assertSame(['node' => [20011988 => 20011988]], $access->getCacheTags());
    $this->assertSame('default', $access->getCacheBin());
    $this->assertSame(1500, $access->getCacheMaxAge());

    // andIf(); 1st has custom tags, max-age, 2nd has custom contexts and max-age.
    $access = AccessResult::allowed()->cachePerUser()->setCacheMaxAge(43200)
      ->andIf(AccessResult::forbidden()->addCacheTags(['node' => [14031991]])->setCacheMaxAge(86400));
    $this->assertFalse($access->isAllowed());
    $this->assertTrue($access->isForbidden());
    $this->assertTrue($access->isCacheable());
    $this->assertSame(['cache_context.user'], $access->getCacheKeys());
    $this->assertSame(['node' => [14031991 => 14031991]], $access->getCacheTags());
    $this->assertSame('default', $access->getCacheBin());
    $this->assertSame(43200, $access->getCacheMaxAge());

    // orIf(); 1st is cacheable, 2nd isn't.
    $access = AccessResult::allowed()->orIf(AccessResult::allowed()->setCacheable(FALSE));
    $this->assertTrue($access->isAllowed());
    $this->assertFalse($access->isForbidden());
    $this->assertFalse($access->isCacheable());

    // andIf(); 1st is cacheable, 2nd isn't.
    $access = AccessResult::allowed()->andIf(AccessResult::allowed()->setCacheable(FALSE));
    $this->assertTrue($access->isAllowed());
    $this->assertFalse($access->isForbidden());
    $this->assertFalse($access->isCacheable());

    // andIf(); 1st implements CacheableInterface, 2nd doesn't.
    $access = AccessResult::allowed()->andIf($access_without_cacheability);
    $this->assertTrue($access->isAllowed());
    $this->assertFalse($access->isForbidden());
    $this->assertFalse($access->isCacheable());

    // andIf(); 1st doesn't implement CacheableInterface, 2nd does.
    $access = $access_without_cacheability->andIf(AccessResult::allowed());
    $this->assertTrue($access->isAllowed());
    $this->assertFalse($access->isForbidden());
    $this->assertFalse(method_exists($access, 'isCacheable'));
  }

}
