<?php

declare(strict_types=1);

namespace Drupal\Tests\TestTools\Attribute;

use Drupal\TestTools\Attribute\ShareEnvironment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests how the ShareEnvironment attribute is resolved.
 *
 * The fixtures below are plain classes. A test class cannot be used, because
 * every concrete test class must repeat the PHPUnit attributes that these
 * fixtures have no use for.
 *
 * @see \Drupal\PHPStan\Rules\TestClassClassMetadata
 */
#[CoversClass(ShareEnvironment::class)]
#[Group('TestTools')]
class ShareEnvironmentTest extends TestCase {

  /**
   * Tests that a class declaring nothing does not share its environment.
   */
  public function testNoAttribute(): void {
    $this->assertFalse(ShareEnvironment::isEnabledFor(PlainEnvironmentFixture::class));
  }

  /**
   * Tests that the attribute makes a class share its environment.
   */
  public function testAttribute(): void {
    $this->assertTrue(ShareEnvironment::isEnabledFor(SharedEnvironmentFixture::class));
  }

  /**
   * Tests that a class can state that it needs its own environment.
   *
   * The declaration does nothing while sharing is opt-in, and everything once
   * sharing becomes the default.
   */
  public function testAttributeOptingOut(): void {
    $this->assertFalse(ShareEnvironment::isEnabledFor(DedicatedEnvironmentFixture::class));
  }

  /**
   * Tests that the attribute is not inherited.
   *
   * Every test class declares for itself. A class that inherited the attribute
   * would start sharing an environment nobody audited it for.
   */
  public function testAttributeIsNotInherited(): void {
    $this->assertFalse(ShareEnvironment::isEnabledFor(UndeclaredEnvironmentFixture::class));
  }

}

/**
 * A class declaring nothing.
 */
class PlainEnvironmentFixture {}

/**
 * A class sharing its environment.
 */
#[ShareEnvironment]
class SharedEnvironmentFixture {}

/**
 * A class overriding the declaration of the class it extends.
 */
#[ShareEnvironment(FALSE)]
class DedicatedEnvironmentFixture extends SharedEnvironmentFixture {}

/**
 * A class declaring nothing, below one that shares its environment.
 */
class UndeclaredEnvironmentFixture extends SharedEnvironmentFixture {}
