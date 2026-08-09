<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\TestTools\Attribute\Skip;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

/**
 * Provides the root base class for all Drupal core tests.
 *
 * @ingroup testing
 */
abstract class DrupalTestCase extends TestCase {

  use DrupalTestCaseTrait;

  /**
   * The Drupal root directory.
   */
  protected string $root;

  /**
   * Supports skipping tests with the Skip attribute.
   *
   * This is run with a high priority to prevent any setUp() functions from
   * executing, avoiding any Drupal bootstrap.
   *
   * @internal
   */
  #[Before(200)]
  final protected function skipTestWithAttribute(): void {
    $reflection = new \ReflectionMethod(static::class, $this->name());
    $attribute = $reflection->getAttributes(Skip::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? NULL;
    if ($attribute) {
      static::markTestSkipped($attribute->newInstance()->message);
    }
  }

  /**
   * Ensure that the $root property is set initially.
   *
   * This is run with a high priority since other test setup code that runs in
   * #[Before] hooks or setUp() requires access to $root.
   *
   * @internal
   */
  #[Before(100)]
  final protected function setUpRoot(): void {
    if (isset($this->root)) {
      throw new \LogicException("setUpRoot should be called exactly once by PHPUnit's Before test hook and root overrides should happen after.");
    }
    $this->root = dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)), 2);
  }

  /**
   * Returns the Drupal root directory.
   *
   * @return string
   *   The Drupal root directory.
   *
   * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Access
   *   $this->root directly.
   *
   * @see https://www.drupal.org/node/3574112
   */
  protected function getDrupalRoot(): string {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Access $this->root directly. See https://www.drupal.org/node/3574112', E_USER_DEPRECATED);
    return dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)), 2);
  }

}
