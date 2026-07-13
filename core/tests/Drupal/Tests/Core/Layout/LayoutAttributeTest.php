<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Layout;

use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Layout attribute.
 */
#[CoversClass(Layout::class)]
#[Group('Layout')]
class LayoutAttributeTest extends UnitTestCase {

  /**
   * Test deprecating plugins without a label or category.
   */
  public function testMissingProperties(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageIs('The "layout_without_label" layout plugin must have at least one of the label or deriver properties.');
    new Layout('layout_without_label');
  }

}
