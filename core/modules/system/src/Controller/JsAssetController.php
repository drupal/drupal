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
    $language = $this->languageManager()->getLanguage($request->get('language'));
    [$js_assets_header, $js_assets_footer] = $this->assetResolver->getJsAssets($attached_assets, FALSE, $language);
    $scope = $request->get('scope');
    if (!isset($scope)) {
      throw new BadRequestHttpException('The URL must have a scope query argument.');
    }
    $assets = $scope === 'header' ? $js_assets_header : $js_assets_footer;
    // While the asset resolver will find settings, these are never aggregated,
    // so filter them out.
    unset($assets['drupalSettings']);
    return $this->grouper->group($assets);
  }

}
