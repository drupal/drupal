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
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The "layout_without_label" layout plugin must have at least one of the label or deriver properties.');
    new Layout('layout_without_label');
  }

}
