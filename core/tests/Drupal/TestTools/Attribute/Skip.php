<?php

declare(strict_types=1);

namespace Drupal\TestTools\Attribute;

/**
 * Defines an attribute to mark tests as skipped.
 *
 * Using this attribute to skip tests is preferred instead of
 * Assert::markTestSkipped(). DrupalTestCase::skipTestWithAttribute() will mark
 * tests with this attribute as skipped before Drupal is bootstrapped.
 *
 * @see \PHPUnit\Framework\Assert::markTestSkipped()
 * @see \Drupal\Tests\DrupalTestCase::skipTestWithAttribute()
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Skip {

  /**
   * Constructs a Skip object.
   *
   * @param string $message
   *   (optional) Information about why the test is skipped.
   */
  public function __construct(public readonly string $message = '') {}

}
