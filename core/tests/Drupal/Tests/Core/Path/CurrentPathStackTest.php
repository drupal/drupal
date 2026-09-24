<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Path;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests Drupal\Core\Path\CurrentPathStack.
 */
#[CoversClass(CurrentPathStack::class)]
#[Group('Path')]
class CurrentPathStackTest extends UnitTestCase {

  /**
   * Tests that a path is kept per request, defaulting to the path info.
   */
  public function testPathIsKeptPerRequest(): void {
    $request_stack = new RequestStack();
    $current = Request::create('/node/1');
    $request_stack->push($current);
    $stack = new CurrentPathStack($request_stack);
    $other = Request::create('/node/2');

    $this->assertSame('/node/1', $stack->getPath());
    $stack->setPath('/system/path', $other);

    $this->assertSame('/system/path', $stack->getPath($other));
    $this->assertSame('/node/1', $stack->getPath($current));
  }

  /**
   * Tests that a request nothing else references is released.
   *
   * Code that matches many synthetic requests against the router in one
   * process, such as re-tracking entity usage from drush, would otherwise keep
   * every one of them for the life of the process.
   */
  public function testRequestNothingElseReferencesIsReleased(): void {
    $stack = new CurrentPathStack(new RequestStack());
    $request = Request::create('/node/1');
    $stack->setPath('/node/1', $request);
    $reference = \WeakReference::create($request);

    unset($request);

    $this->assertNull($reference->get());
  }

}
