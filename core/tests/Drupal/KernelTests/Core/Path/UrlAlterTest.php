<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Path;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the capability to alter URLs.
 *
 * @see \Drupal\Core\Routing\UrlGenerator::processPath
 */
#[Group('Path')]
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
