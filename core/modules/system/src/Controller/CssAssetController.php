<?php

namespace Drupal\system\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
  public function deliver(Request $request, string $file_name) {
    if ($request->query->has('libraries') && !$request->query->has('category')) {
      throw new BadRequestHttpException('Category must be passed when libraries are passed.');
    }
    if ($request->query->has('category') && !$request->query->has('libraries')) {
      throw new BadRequestHttpException('Libraries must be passed when category is passed.');
    }
    return parent::deliver($request, $file_name);
  }

  /**
   * {@inheritdoc}
   */
  protected function getGroups(AttachedAssetsInterface $attached_assets, Request $request): array {
    $language = $this->languageManager()->getLanguage($request->query->get('language'));
    if ($request->query->has('libraries')) {
      $assets = $this->assetResolver->getCssAssets($attached_assets, FALSE, $language, FALSE);
    }
    else {
      $assets = $this->assetResolver->getCssAssets($attached_assets, FALSE, $language);
    }

    if ($request->query->has('category')) {
      // Filter out files that don't belong to this group.
      $category = $request->query->getString('category');
      foreach ($assets as $key => $asset) {
        if ($asset['category'] !== $category) {
          unset($assets[$key]);
        }
      }
    }
    return $this->grouper->group($assets);
  }

}
