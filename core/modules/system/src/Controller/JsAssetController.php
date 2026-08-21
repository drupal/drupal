<?php

namespace Drupal\system\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Asset\AssetGroupSetHashTrait;

/**
 * Defines a controller to serve Javascript aggregates.
 */
class JsAssetController extends AssetControllerBase {

  use AssetGroupSetHashTrait;

  /**
   * {@inheritdoc}
   */
  protected string $contentType = 'text/javascript';

  /**
   * {@inheritdoc}
   */
  protected string $assetType = 'js';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('stream_wrapper_manager'),
      $container->get('library.dependency_resolver'),
      $container->get('asset.resolver'),
      $container->get('theme.initialization'),
      $container->get('theme.manager'),
      $container->get('asset.js.collection_grouper'),
      $container->get('asset.js.collection_optimizer'),
      $container->get('asset.js.dumper'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getGroups(AttachedAssetsInterface $attached_assets, Request $request): array {
    // The header and footer scripts are two distinct sets of asset groups. The
    // $group_key is not sufficient to find the group, we also need to locate it
    // within either the header or footer set.
    $language = $this->languageManager()->getLanguage($request->query->get('language'));

    if ($request->query->has('libraries')) {
      [$js_assets_header, $js_assets_footer] = $this->assetResolver->getJsAssets($attached_assets, FALSE, $language, FALSE);
      // When the libraries query argument is set, the aggregate contains every
      // file from each of the listed libraries. However, which scope an
      // individual library appears in is determined by the HTML page request as
      // well as the library definition, depending on the dependencies between
      // libraries. This means we must ignore the scope when collecting the
      // libraries and get them from both the header and footer in order.
      // When building the array, also remove the scope from each asset so that
      // it is ignored when calculating the hash, as in
      // JsCollectionOptimizerLazy::optimize().
      $assets = [];
      unset($js_assets_header['drupalSettings'], $js_assets_footer['drupalSettings']);
      foreach ($js_assets_header as $key => $asset) {
        unset($asset['scope']);
        $assets[$key] = $asset;
      }
      foreach ($js_assets_footer as $key => $asset) {
        unset($asset['scope']);
        $assets[$key] = $asset;
      }
    }
    else {
      $scope = $request->query->get('scope');
      if (!isset($scope)) {
        throw new BadRequestHttpException('The URL must have a scope query argument.');
      }
      [$js_assets_header, $js_assets_footer] = $this->assetResolver->getJsAssets($attached_assets, FALSE, $language);
      $assets = $scope === 'header' ? $js_assets_header : $js_assets_footer;
      unset($assets['drupalSettings']);
    }
    return $this->grouper->group($assets);
  }

}
