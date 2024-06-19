<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Kernel;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;

/**
 * Tests the deprecation of views_ui_truncate() function.
 *
 * @group views_ui
 */
class TruncateDeprecateTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['views', 'views_ui'];

  /**
   * Tests the deprecation of views_ui_truncate() replaced by Unicode::truncate.
   *
   * @group legacy
   */
  public function testDeprecateViewsUiTruncate(): void {
    $string = 'one two three four five six seven eight nine ten eleven twelve thirteen fourteen fifteen';
    $short_string = views_ui_truncate($string, 80);
    $this->expectDeprecation('views_ui_truncate() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Component\Utility\Unicode::truncate(). See https://www.drupal.org/node/3408283');
  }

}
