<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Asset;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests asset aggregation.
 *
 * @group asset
 */
class UnversionedAssetTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The file assets path settings value.
   *
   * @var string
   */
  protected $fileAssetsPath;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'unversioned_assets_test'];

  /**
   * Tests that unversioned assets cause a new filename when changed.
   */
  public function testUnversionedAssets(): void {
    $this->fileAssetsPath = $this->publicFilesDirectory;
    file_put_contents('public://test.css', '.original-content{display:none;}');
    // Test aggregation with a custom file_assets_path.
    $this->config('system.performance')->set('css', [
      'preprocess' => TRUE,
      'gzip' => TRUE,
    ])->save();
    $this->config('system.performance')->set('js', [
      'preprocess' => TRUE,
      'gzip' => TRUE,
    ])->save();

    // Ensure that the library discovery cache is empty before the page is
    // requested and that updated asset URLs are rendered.
    \Drupal::service('cache.data')->deleteAll();
    \Drupal::service('cache.page')->deleteAll();
    $this->drupalGet('<front>');
    $session = $this->getSession();
    $page = $session->getPage();

    $style_elements = $page->findAll('xpath', '//link[@href and @rel="stylesheet"]');
    $this->assertNotEmpty($style_elements);
    $href = NULL;
    foreach ($style_elements as $element) {
      $href = $element->getAttribute('href');
      $url = $this->getAbsoluteUrl($href);
      // Not every script or style on a page is aggregated.
      if (!str_contains($url, $this->fileAssetsPath)) {
        continue;
      }
      $session = $this->getSession();
      $session->visit($url);
      $this->assertSession()->statusCodeEquals(200);
      $aggregate = $session = $session->getPage()->getContent();
      $this->assertStringContainsString('original-content', $aggregate);
      $this->assertStringNotContainsString('extra-stuff', $aggregate);
    }
    $file = file_get_contents('public://test.css') . '.extra-stuff{display:none;}';
    file_put_contents('public://test.css', $file);
    // Clear the library discovery and page caches again so that new URLs are
    // generated.
    \Drupal::service('cache.data')->deleteAll();
    \Drupal::service('cache.page')->deleteAll();
    $this->drupalGet('<front>');
    $session = $this->getSession();
    $page = $session->getPage();
    $style_elements = $page->findAll('xpath', '//link[@href and @rel="stylesheet"]');
    $this->assertNotEmpty($style_elements);
    foreach ($style_elements as $element) {
      $new_href = $element->getAttribute('href');
      $this->assertNotSame($new_href, $href);
      $url = $this->getAbsoluteUrl($new_href);
      // Not every script or style on a page is aggregated.
      if (!str_contains($url, $this->fileAssetsPath)) {
        continue;
      }
      $session = $this->getSession();
      $session->visit($url);
      $this->assertSession()->statusCodeEquals(200);
      $aggregate = $session = $session->getPage()->getContent();
      $this->assertStringContainsString('original-content', $aggregate);
      $this->assertStringContainsString('extra-stuff', $aggregate);
    }
  }

}
