<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Asset;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\system\Controller\AssetControllerBase;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests sanitization of error messages emitted by AssetControllerBase.
 */
#[Group('asset')]
#[RunTestsInSeparateProcesses]
#[CoversMethod(AssetControllerBase::class, 'deliver')]
class AssetXssTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A dataProvider for JS and CSS asset tests.
   *
   * @return array
   *   - Array of inputs for the test URL
   */
  public static function providerAssetUrl() {
    $query = [
      'language' => 'en',
      'delta' => 1,
      'theme' => 'drupal',
      'include' => '<img src=x onerror=alert("xss")>',
    ];
    return [
      [
        'path' => '/js/js_foo.js',
        'query' => $query,
      ],
      [
        'path' => '/css/css_foo.css',
        'query' => $query,
      ],
    ];
  }

  /**
   * Test sanitization of the error message for an invalid asset.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  #[DataProvider('providerAssetUrl')]
  public function testAssetUrl($path, $query): void {
    $path = PublicStream::basePath() . $path;

    $this->drupalGet($path, ['query' => $query]);
    $this->assertSession()->statusCodeEquals(400);
    $this->assertSession()->responseContains('library name must include at least one slash');
    $this->assertSession()->elementNotExists('xpath', '//img[contains(@onerror, "alert")]');

    // Swap the XSS payload into the exclude parameter.
    $query['exclude'] = $query['include'];
    $query['include'] = 'foo/bar';

    $this->drupalGet($path, ['query' => $query]);
    $this->assertSession()->statusCodeEquals(400);
    $this->assertSession()->responseContains('library name must include at least one slash');
    $this->assertSession()->elementNotExists('xpath', '//img[contains(@onerror, "alert")]');
  }

}
