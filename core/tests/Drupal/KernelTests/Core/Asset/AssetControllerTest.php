<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Asset;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Controller\AssetControllerBase;
use Drupal\system\Controller\CssAssetController;
use Drupal\system\Controller\JsAssetController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Tests that the asset controllers reject invalid aggregate requests.
 *
 * @covers \Drupal\system\Controller\AssetControllerBase::deliver
 * @group asset
 * @runTestsInSeparateProcesses
 */
class AssetControllerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['common_test', 'system'];

  /**
   * The asset resolver service.
   */
  protected AssetResolverInterface $assetResolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->assetResolver = $this->container->get('asset.resolver');
  }

  /**
   * Tests that deliver() throws BadRequestHttpException for invalid requests.
   */
  public function testDeliverBadRequests(): void {
    $language = \Drupal::languageManager()->getCurrentLanguage();
    $controller = CssAssetController::create($this->container);
    $hash = $this->randomMachineName();
    // Valid base params for assertions that must pass the early checks.
    $valid_params = [
      'delta' => 0,
      'language' => $language->getId(),
      'theme' => 'stark',
      'include' => UrlHelper::compressQueryParameter('common_test/external'),
    ];

    // Missing theme.
    $this->assertDeliverThrows(
      $controller,
      Request::create('/dummy', 'GET', array_diff_key($valid_params, ['theme' => ''])),
      'css_' . $hash . '.css',
      'The theme must be passed as a query argument',
    );

    // Missing delta.
    $this->assertDeliverThrows(
      $controller,
      Request::create('/dummy', 'GET', array_diff_key($valid_params, ['delta' => ''])),
      'css_' . $hash . '.css',
      'The numeric delta must be passed as a query argument',
    );

    // Non-numeric delta.
    $this->assertDeliverThrows(
      $controller,
      Request::create('/dummy', 'GET', array_merge($valid_params, ['delta' => 'nan'])),
      'css_' . $hash . '.css',
      'The numeric delta must be passed as a query argument',
    );

    // Missing language.
    $this->assertDeliverThrows(
      $controller,
      Request::create('/dummy', 'GET', array_diff_key($valid_params, ['language' => ''])),
      'css_' . $hash . '.css',
      'The language must be passed as a query argument',
    );

    // Missing include.
    $this->assertDeliverThrows(
      $controller,
      Request::create('/dummy', 'GET', array_diff_key($valid_params, ['include' => ''])),
      'css_' . $hash . '.css',
      'The libraries to include must be passed as a query argument',
    );

    // Wrong filename prefix (JS prefix for a CSS controller).
    $this->assertDeliverThrows(
      $controller,
      Request::create('/dummy', 'GET', $valid_params),
      'js_' . $hash . '.css',
      'The filename prefix must match the file extension',
    );

    // No hash segment in filename.
    $this->assertDeliverThrows(
      $controller,
      Request::create('/dummy', 'GET', $valid_params),
      'css.css',
      'Invalid filename',
    );

    // Library name without a slash in include.
    $this->assertDeliverThrows(
      $controller,
      Request::create('/dummy', 'GET', array_merge($valid_params, [
        'include' => UrlHelper::compressQueryParameter('noslash'),
      ])),
      'css_' . $hash . '.css',
      'The "noslash" library name must include at least one slash.',
    );

    // Library name without a slash in exclude.
    $this->assertDeliverThrows(
      $controller,
      Request::create('/dummy', 'GET', array_merge($valid_params, [
        'exclude' => UrlHelper::compressQueryParameter('noslash'),
      ])),
      'css_' . $hash . '.css',
      'The "noslash" library name must include at least one slash.',
    );

    // Out-of-bounds delta.
    $this->assertDeliverThrows(
      $controller,
      Request::create('/dummy', 'GET', array_merge($valid_params, ['delta' => 999])),
      'css_' . $hash . '.css',
      'Invalid filename.',
    );

    // External group delta: requesting an aggregate for a non-file group.
    $css_assets = $this->assetResolver->getCssAssets(AttachedAssets::createFromRenderArray(['#attached' => ['library' => ['common_test/external']]]), FALSE, $language);
    $groups = $this->container->get('asset.css.collection_grouper')->group($css_assets);
    $external_groups = array_filter($groups, static fn($g) => $g['type'] === 'external');
    $this->assertNotEmpty($external_groups, 'common_test/external must contain an external CSS group.');
    $external_delta = array_key_first($external_groups);

    $this->assertDeliverThrows(
      $controller,
      Request::create('/dummy', 'GET', array_merge($valid_params, ['delta' => $external_delta])),
      'css_' . $hash . '.css',
      'The requested asset group is not aggregated.',
    );

    // Non-aggregated group delta: requesting an aggregate for a file group that
    // has preprocess disabled.
    $css_assets = $this->assetResolver->getCssAssets(AttachedAssets::createFromRenderArray(['#attached' => ['library' => ['common_test/no-preprocess']]]), FALSE, $language);
    $groups = $this->container->get('asset.css.collection_grouper')->group($css_assets);
    $no_preprocess_groups = array_filter($groups, static fn($g) => $g['type'] === 'file' && $g['preprocess'] === FALSE);
    $this->assertNotEmpty($no_preprocess_groups, 'common_test/no-preprocess must contain a CSS group with preprocess disabled.');
    $no_preprocess_delta = array_key_first($no_preprocess_groups);

    $this->assertDeliverThrows(
      $controller,
      Request::create('/dummy', 'GET', array_merge($valid_params, [
        'delta' => $no_preprocess_delta,
        'include' => UrlHelper::compressQueryParameter('common_test/no-preprocess'),
      ])),
      'css_' . $hash . '.css',
      'The requested asset group is not aggregated.',
    );

    // Missing scope; JS-specific, since JsAssetController::getGroups() needs
    // it to distinguish header from footer assets.
    $js_controller = JsAssetController::create($this->container);
    $this->assertDeliverThrows(
      $js_controller,
      Request::create('/dummy', 'GET', $valid_params),
      'js_' . $hash . '.js',
      'The URL must have a scope query argument.',
    );
  }

  /**
   * Asserts that deliver() throws a specific BadRequestHttpException.
   *
   * @param \Drupal\system\Controller\AssetControllerBase $controller
   *   The asset controller to call.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to deliver.
   * @param string $filename
   *   The aggregate filename to request.
   * @param string $expected_message
   *   The expected exception message.
   */
  private function assertDeliverThrows(AssetControllerBase $controller, Request $request, string $filename, string $expected_message): void {
    try {
      $controller->deliver($request, $filename);
      $this->fail("Expected BadRequestHttpException: $expected_message");
    }
    catch (BadRequestHttpException $e) {
      $this->assertSame($expected_message, $e->getMessage());
    }
  }

}
