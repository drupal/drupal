<?php

namespace Drupal\system\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Asset\AssetGroupSetHashTrait;

/**
 * Defines a controller to serve CSS aggregates.
 */
class CssAssetController extends AssetControllerBase {

  use AssetGroupSetHashTrait;

  /**
   * {@inheritdoc}
   */
  protected string $contentType = 'text/css';

  /**
   * {@inheritdoc}
   */
  protected string $assetType = 'css';

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
      $container->get('asset.css.collection_grouper'),
      $container->get('asset.css.collection_optimizer'),
      $container->get('asset.css.dumper'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getGroups(AttachedAssetsInterface $attached_assets, Request $request): array {
    $language = $this->languageManager()->getLanguage($request->get('language'));
    $assets = $this->assetResolver->getCssAssets($attached_assets, FALSE, $language);
    return $this->grouper->group($assets);
  }

}
