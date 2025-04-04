<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Path;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the capability to alter URLs.
 *
 * @group Path
 *
 * @see \Drupal\Core\Routing\UrlGenerator::processPath
 */
class UrlAlterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['path', 'url_alter_test', 'user'];

  /**
   * Tests altering outbound query string.
   */
  public function testUrlWithQueryString(): void {
    $url = Url::fromRoute('user.login');
    $this->assertEquals(\Drupal::request()->getBaseUrl() . '/user/login?foo=bar', $url->toString());
  }

}
