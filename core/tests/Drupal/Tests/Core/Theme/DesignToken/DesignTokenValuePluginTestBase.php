<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\DesignToken;

use Drupal\Tests\UnitTestCase;

/**
 * Base class for testing design token value plugins.
 */
abstract class DesignTokenValuePluginTestBase extends UnitTestCase {

  /**
   * The tested plugin ID.
   */
  protected string $pluginId;

  /**
   * Tests toDtcg.
   */
  abstract public function testToDtcg(mixed $value, mixed $expected): void;

  /**
   * Tests toCss.
   */
  abstract public function testToCss(mixed $value, string $expected): void;

}
