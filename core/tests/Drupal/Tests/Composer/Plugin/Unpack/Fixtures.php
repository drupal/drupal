<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer\Plugin\Unpack;

use Drupal\Tests\Composer\Plugin\FixturesBase;

/**
 * Fixture for testing the unpack composer plugin.
 */
class Fixtures extends FixturesBase {

  /**
   * {@inheritdoc}
   */
  public function projectRoot(): string {
    return realpath(__DIR__) . '/../../../../../../../composer/Plugin/RecipeUnpack';
  }

  /**
   * {@inheritdoc}
   */
  public function allFixturesDir(): string {
    return realpath(__DIR__ . '/fixtures');
  }

  /**
   * {@inheritdoc}
   */
  public function tmpDir(string $prefix): string {
    $prefix .= static::persistentPrefix();
    $tmpDir = sys_get_temp_dir() . '/unpack-' . $prefix . uniqid(md5($prefix . microtime()), TRUE);
    $this->tmpDirs[] = $tmpDir;
    return $tmpDir;
  }

}
