<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Plugin\Validation\Constraint\HtmlConstraint;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Tests the Html constraint.
 */
#[CoversClass(HtmlConstraint::class)]
#[Group('Validation')]
class HtmlConstraintTest extends UnitTestCase {

  /**
   * Tests that an InvalidArgumentException is thrown on a bad parsing mode.
   */
  public function testInvalidMode(): void {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid HTML parsing mode. the `mode` argument must be "fragment" or "document".');
    new HtmlConstraint('unsupported');
  }

}
