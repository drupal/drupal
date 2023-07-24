<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Asset;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Tests\BrowserTestBase;

// cspell:ignore abcdefghijklmnop

/**
 * Tests asset aggregation.
 *
 * @group asset
 */
class AssetOptimizationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The file assets path settings value.
   */
  protected $fileAssetsPath;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests that asset aggregates are rendered and created on disk.
   */
  public function testAssetAggregation(): void {
    // Test aggregation with a custom file_assets_path.
    $this->fileAssetsPath = $this->publicFilesDirectory . '/test-assets';
    $settings['settings']['file_assets_path'] = (object) [
      'value' => $this->fileAssetsPath,
      'required' => TRUE,
    ];
    $this->doTestAggregation($settings);

    // Test aggregation with no configured file_assets_path or file_public_path,
    // since tests run in a multisite, this tests multisite installs where
    // settings.php is the default.
    $this->fileAssetsPath = $this->publicFilesDirectory;
    $settings['settings']['file_public_path'] = (object) [
      'value' => NULL,
      'required' => TRUE,
    ];
    $settings['settings']['file_assets_path'] = (object) [
      'value' => NULL,
      'required' => TRUE,
    ];
    $this->doTestAggregation($settings);
  }

  /**
   * Creates a user and requests a page.
   */
  protected function requestPage(): void {
    $user = $this->createUser();
    $this->drupalLogin($user);
    $this->drupalGet('');
  }

  /**
   * Helper to test aggregate file URLs.
   *
   * @param array $settings
   *   A settings array to pass to ::writeSettings()
   */
  protected function doTestAggregation(array $settings): void {
    $this->writeSettings($settings);
    $this->rebuildAll();
    $this->config('system.performance')->set('css', [
      'preprocess' => TRUE,
      'gzip' => TRUE,
    ])->save();
    $this->config('system.performance')->set('js', [
      'preprocess' => TRUE,
      'gzip' => TRUE,
    ])->save();
    $this->requestPage();
    $session = $this->getSession();
    $page = $session->getPage();

    // Collect all the URLs for all the script and styles prior to making any
    // more requests.
    $style_elements = $page->findAll('xpath', '//link[@href and @rel="stylesheet"]');
    $script_elements = $page->findAll('xpath', '//script[@src]');
    $style_urls = [];
    foreach ($style_elements as $element) {
      $style_urls[] = $element->getAttribute('href');
    }
    $script_urls = [];
    foreach ($script_elements as $element) {
      $script_urls[] = $element->getAttribute('src');
    }
    foreach ($style_urls as $url) {
      $this->assertAggregate($url, TRUE, 'text/css');
      // Once the file has been requested once, it's on disk. It is possible for
      // a second request to hit the controller, and then find that another
      // request has created the file already. Actually simulating this race
      // condition is not really possible since it relies on timing. However, by
      // changing the case of the part of the URL that is handled by Drupal
      // routing, we can force the request to be served by Drupal.
      $this->assertAggregate(str_replace($this->fileAssetsPath, strtoupper($this->fileAssetsPath), $url), TRUE, 'text/css');
      $this->assertAggregate($url, FALSE, 'text/css');
      $this->assertInvalidAggregates($url);
    }

    foreach ($script_urls as $url) {
      $this->assertAggregate($url);
      $this->assertAggregate($url, FALSE);
      $this->assertInvalidAggregates($url);
    }
  }

  /**
   * Asserts the aggregate header.
   *
   * @param string $url
   *   The source URL.
   * @param bool $from_php
   *   (optional) Is the result from PHP or disk? Defaults to TRUE (PHP).
   * @param string|null $content_type
   *   The expected content type, or NULL to skip checking.
   */
  protected function assertAggregate(string $url, bool $from_php = TRUE, string $content_type = NULL): void {
    $url = $this->getAbsoluteUrl($url);
    if (!stripos($url, $this->fileAssetsPath) !== FALSE) {
      return;
    }
    $session = $this->getSession();
    $session->visit($url);
    $this->assertSession()->statusCodeEquals(200);
    $headers = $session->getResponseHeaders();
    if (isset($content_type)) {
      $this->assertStringContainsString($content_type, $headers['Content-Type'][0]);
    }
    if ($from_php) {
      $this->assertStringContainsString('no-store', $headers['Cache-Control'][0]);
      $this->assertArrayHasKey('X-Generator', $headers);
    }
    else {
      $this->assertArrayNotHasKey('X-Generator', $headers);
    }
  }

  /**
   * Asserts the aggregate when it is invalid.
   *
   * @param string $url
   *   The source URL.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertInvalidAggregates(string $url): void {
    $url = $this->getAbsoluteUrl($url);
    // Not every script or style on a page is aggregated.
    if (!str_contains($url, $this->fileAssetsPath)) {
      return;
    }
    $session = $this->getSession();
    $session->visit($this->replaceGroupDelta($url));
    $this->assertSession()->statusCodeEquals(200);

    $session->visit($this->omitTheme($url));
    $this->assertSession()->statusCodeEquals(400);

    $session->visit($this->omitInclude($url));
    $this->assertSession()->statusCodeEquals(400);

    $session->visit($this->invalidInclude($url));
    $this->assertSession()->statusCodeEquals(400);

    $session->visit($this->invalidExclude($url));
    $this->assertSession()->statusCodeEquals(400);

    $session->visit($this->replaceFileNamePrefix($url));
    $this->assertSession()->statusCodeEquals(400);

    $session->visit($this->setInvalidLibrary($url));
    $this->assertSession()->statusCodeEquals(200);

    $session->visit($this->replaceGroupHash($url));
    $this->assertSession()->statusCodeEquals(200);
    $headers = $session->getResponseHeaders();
    $this->assertEquals(['no-store, private'], $headers['Cache-Control']);

    // And again to confirm it's not cached on disk.
    $session->visit($this->replaceGroupHash($url));
    $this->assertSession()->statusCodeEquals(200);
    $headers = $session->getResponseHeaders();
    $this->assertEquals(['no-store, private'], $headers['Cache-Control']);
  }

  /**
   * Replaces the delta in the given URL.
   *
   * @param string $url
   *   The source URL.
   *
   * @return string
   *   The URL with the delta replaced.
   */
  protected function replaceGroupDelta(string $url): string {
    $parts = UrlHelper::parse($url);
    $parts['query']['delta'] = 100;
    $query = UrlHelper::buildQuery($parts['query']);
    return $this->getAbsoluteUrl($parts['path'] . '?' . $query . '#' . $parts['fragment']);
  }

  /**
   * Replaces the group hash in the given URL.
   *
   * @param string $url
   *   The source URL.
   *
   * @return string
   *   The URL with the group hash replaced.
   */
  protected function replaceGroupHash(string $url): string {
    $parts = explode('_', $url, 2);
    $hash = strtok($parts[1], '.');
    $parts[1] = str_replace($hash, 'abcdefghijklmnop', $parts[1]);
    return $this->getAbsoluteUrl(implode('_', $parts));
  }

  /**
   * Replaces the filename prefix in the given URL.
   *
   * @param string $url
   *   The source URL.
   *
   * @return string
   *   The URL with the file name prefix replaced.
   */
  protected function replaceFileNamePrefix(string $url): string {
    return str_replace(['/css_', '/js_'], '/xyz_', $url);
  }

  /**
   * Replaces the 'include' entry in the given URL with an invalid value.
   *
   * @param string $url
   *   The source URL.
   *
   * @return string
   *   The URL with the 'include' query set to an invalid value.
   */
  protected function setInvalidLibrary(string $url): string {
    // First replace the hash, so we don't get served the actual file on disk.
    $url = $this->replaceGroupHash($url);
    $parts = UrlHelper::parse($url);
    $include = explode(',', UrlHelper::uncompressQueryParameter($parts['query']['include']));
    $include[] = 'system/llama';
    $parts['query']['include'] = UrlHelper::compressQueryParameter(implode(',', $include));

    $query = UrlHelper::buildQuery($parts['query']);
    return $this->getAbsoluteUrl($parts['path'] . '?' . $query . '#' . $parts['fragment']);
  }

  /**
   * Removes the 'theme' query parameter from the given URL.
   *
   * @param string $url
   *   The source URL.
   *
   * @return string
   *   The URL with the 'theme' omitted.
   */
  protected function omitTheme(string $url): string {
    // First replace the hash, so we don't get served the actual file on disk.
    $url = $this->replaceGroupHash($url);
    $parts = UrlHelper::parse($url);
    unset($parts['query']['theme']);
    $query = UrlHelper::buildQuery($parts['query']);
    return $this->getAbsoluteUrl($parts['path'] . '?' . $query . '#' . $parts['fragment']);
  }

  /**
   * Removes the 'include' query parameter from the given URL.
   *
   * @param string $url
   *   The source URL.
   *
   * @return string
   *   The URL with the 'include' parameter omitted.
   */
  protected function omitInclude(string $url): string {
    // First replace the hash, so we don't get served the actual file on disk.
    $url = $this->replaceGroupHash($url);
    $parts = UrlHelper::parse($url);
    unset($parts['query']['include']);
    $query = UrlHelper::buildQuery($parts['query']);
    return $this->getAbsoluteUrl($parts['path'] . '?' . $query . '#' . $parts['fragment']);
  }

  /**
   * Replaces the 'include' query parameter with an invalid value.
   *
   * @param string $url
   *   The source URL.
   *
   * @return string
   *   The URL with 'include' set to an arbitrary string.
   */
  protected function invalidInclude(string $url): string {
    // First replace the hash, so we don't get served the actual file on disk.
    $url = $this->replaceGroupHash($url);
    $parts = UrlHelper::parse($url);
    $parts['query']['include'] = 'abcdefghijklmnop';
    $query = UrlHelper::buildQuery($parts['query']);
    return $this->getAbsoluteUrl($parts['path'] . '?' . $query . '#' . $parts['fragment']);
  }

  /**
   * Adds an invalid 'exclude' query parameter with an invalid value.
   *
   * @param string $url
   *   The source URL.
   *
   * @return string
   *   The URL with 'exclude' set to an arbitrary string.
   */
  protected function invalidExclude(string $url): string {
    // First replace the hash, so we don't get served the actual file on disk.
    $url = $this->replaceGroupHash($url);
    $parts = UrlHelper::parse($url);
    $parts['query']['exclude'] = 'abcdefghijklmnop';
    $query = UrlHelper::buildQuery($parts['query']);
    return $this->getAbsoluteUrl($parts['path'] . '?' . $query . '#' . $parts['fragment']);
  }

}
