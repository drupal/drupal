<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Layout;

use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests the Layout attribute.
 */
#[CoversClass(Layout::class)]
#[Group('Layout')]
#[IgnoreDeprecations]
class LayoutAttributeTest extends UnitTestCase {

  /**
   * Test deprecating plugins without a label or category.
   */
  public function testDeprecatedMissingProperties(): void {
    $this->expectDeprecation('A layout plugin not having at least one of the label or deriver properties is deprecated in drupal:11.4.0 and having at least one of these properties will be enforced in drupal:12.0.0. See https://www.drupal.org/node/3464076');
    new Layout('layout_without_label');
  }

}
