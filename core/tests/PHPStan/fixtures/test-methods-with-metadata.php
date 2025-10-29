<?php

// phpcs:ignoreFile

declare(strict_types=1);

namespace Drupal\Tests\Core\Foo;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

class FriendsTest extends TestCase {

  #[Group('Joey')]
  public function testWithAttribute(): void {
  }

  /**
   * @group Chandler
   */
  #[Group('Rachel')]
  public function testWithAttributeAndForbiddenAnnotation(): void {
  }

  /**
   * @legacy-covers ::phoebe
   */
  #[Group('Ross')]
  public function testWithAttributeAndAllowedAnnotation(): void {
  }

  /**
   * @group Janice
   */
  public function testWithForbiddenAnnotation(): void {
  }

  /**
   * @see Monica
   */
  public function testWithAllowedAnnotation(): void {
  }

  /**
   * This test method has a legacy covers annotation.
   *
   * @legacy-covers ::ben
   */
  public function testWithLegacyCoversAnnotation(): void {
  }

  public function testNoMetadata(): void {
  }

}

class ExtendFriendsTest extends FriendsTest {
}

trait FriendsTrait {

  #[Group('Joey')]
  public function testInTraitWithAttribute(): void {
  }

  /**
   * @group Chandler
   */
  #[Group('Rachel')]
  public function testInTraitWithAttributeAndForbiddenAnnotation(): void {
  }

  /**
   * @legacy-covers ::phoebe
   */
  #[Group('Ross')]
  public function testInTraitWithAttributeAndAllowedAnnotation(): void {
  }

  /**
   * @group Janice
   */
  public function testInTraitWithForbiddenAnnotation(): void {
  }

  /**
   * @see Monica
   */
  public function testInTraitWithAllowedAnnotation(): void {
  }

  /**
   * This test method has a legacy covers annotation.
   *
   * @legacy-covers ::ben
   */
  public function testInTraitWithLegacyCoversAnnotation(): void {
  }

  public function testInTraitNoMetadata(): void {
  }

}

class FriendsWithFriendsTraitTest extends TestCase {

  use FriendsTrait;

}

class FriendsNotATestClass {

  public function noMetadata(): void {
  }

}
