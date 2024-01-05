<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Unit\Plugin\migrate\process;

use Drupal\block\Plugin\migrate\process\BlockTheme;
use Drupal\Core\Config\Config;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the deprecation notices of the block theme.
 *
 * @group legacy
 */
class BlockThemeDeprecationTest extends UnitTestCase {

  /**
   * Tests the deprecation in the constructor.
   */
  public function testConstructorDeprecation(): void {
    $this->expectDeprecation('Calling Drupal\block\Plugin\migrate\process\BlockTheme::__construct() with the $migration argument is deprecated in drupal:10.1.0 and is removed in drupal:11.0.0. See https://www.drupal.org/node/3323212');
    $migration = $this->prophesize(MigrationInterface::class);
    $config = $this->prophesize(Config::class);
    new BlockTheme(
      [],
      '',
      [],
      $migration->reveal(),
      $config->reveal(),
      []
    );
  }

}
