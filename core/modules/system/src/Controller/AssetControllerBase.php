<?php

namespace Drupal\system\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Asset\AssetCollectionGrouperInterface;
use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Asset\AssetDumperUriInterface;
use Drupal\Core\Asset\AssetGroupSetHashTrait;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Asset\LibraryDependencyResolverInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\system\FileDownloadController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Defines a controller to serve asset aggregates.
 */
abstract class AssetControllerBase extends FileDownloadController {

  use AssetGroupSetHashTrait;

  /**
   * The asset type.
   *
   * @var string
   */
  protected string $assetType;

  /**
   * The aggregate file extension.
   *
   * @var string
   */
  protected string $fileExtension;

  /**
   * The asset aggregate content type to send as Content-Type header.
   *
   * @var string
   */
  protected string $contentType;

  /**
   * The cache control header to use.
   *
   * Headers sent from PHP can never perfectly match those sent when the
   * file is served by the filesystem, so ensure this request does not get
   * cached in either the browser or reverse proxies. Subsequent requests
   * for the file will be served from disk and be cached. This is done to
   * avoid situations such as where one CDN endpoint is serving a version
   * cached from PHP, while another is serving a version cached from disk.
   * Should there be any discrepancy in behavior between those files, this
   * can make debugging very difficult.
   */
  protected const CACHE_CONTROL = 'private, no-store';

  /**
   * Constructs an object derived from AssetControllerBase.
   *
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   The stream wrapper manager.
   * @param \Drupal\Core\Asset\LibraryDependencyResolverInterface $libraryDependencyResolver
   *   The library dependency resolver.
   * @param \Drupal\Core\Asset\AssetResolverInterface $assetResolver
   *   The asset resolver.
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $themeInitialization
   *   The theme initializer.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   * @param \Drupal\Core\Asset\AssetCollectionGrouperInterface $grouper
   *   The asset grouper.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $optimizer
   *   The asset collection optimizer.
   * @param \Drupal\Core\Asset\AssetDumperUriInterface $dumper
   *   The asset dumper.
   */
  public function __construct(
    StreamWrapperManagerInterface $streamWrapperManager,
    protected readonly LibraryDependencyResolverInterface $libraryDependencyResolver,
    protected readonly AssetResolverInterface $assetResolver,
    protected readonly ThemeInitializationInterface $themeInitialization,
    protected readonly ThemeManagerInterface $themeManager,
    protected readonly AssetCollectionGrouperInterface $grouper,
    protected readonly AssetCollectionOptimizerInterface $optimizer,
    protected readonly AssetDumperUriInterface $dumper,
  ) {
    parent::__construct($streamWrapperManager);
    $this->fileExtension = $this->assetType;
  }

  /**
   * Generates an aggregate, given a filename.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $file_name
   *   The file to deliver.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\Response
   *   The transferred file as response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when the filename is invalid or an invalid query argument is
   *   supplied.
   */
  public function deliver(Request $request, string $file_name) {
    $uri = 'assets://' . $this->assetType . '/' . $file_name;

    // Check to see whether a file matching the $uri already exists, this can
    // happen if it was created while this request was in progress.
    if (file_exists($uri)) {
      return new BinaryFileResponse($uri, 200, [
        'Cache-control' => static::CACHE_CONTROL,
        // @todo: remove the explicit setting of Content-Type once this is
        // fixed in https://www.drupal.org/project/drupal/issues/3172550.
        'Content-Type' => $this->contentType,
      ]);
    }

    // First validate that the request is valid enough to produce an asset group
    // aggregate. The theme must be passed as a query parameter, since assets
    // always depend on the current theme.
    if (!$request->query->has('theme')) {
      throw new BadRequestHttpException('The theme must be passed as a query argument');
    }
    if (!$request->query->has('delta') || !is_numeric($request->query->get('delta'))) {
      throw new BadRequestHttpException('The numeric delta must be passed as a query argument');
    }
    if (!$request->query->has('language')) {
      throw new BadRequestHttpException('The language must be passed as a query argument');
    }
    if (!$request->query->has('include')) {
      throw new BadRequestHttpException('The libraries to include must be passed as a query argument');
    }
    $file_parts = explode('_', basename($file_name, '.' . $this->fileExtension), 2);
    // Ensure the filename is correctly prefixed.
    if ($file_parts[0] !== $this->fileExtension) {
      throw new BadRequestHttpException('The filename prefix must match the file extension');
    }

    // The hash is the second segment of the filename.
    if (!isset($file_parts[1])) {
      throw new BadRequestHttpException('Invalid filename');
    }
    $received_hash = $file_parts[1];

    // Now build the asset groups based on the libraries.  It requires the full
    // set of asset groups to extract and build the aggregate for the group we
    // want, since libraries may be split across different asset groups.
    $theme = $request->query->get('theme');
    $active_theme = $this->themeInitialization->initTheme($theme);
    $this->themeManager->setActiveTheme($active_theme);

    $attached_assets = new AttachedAssets();
    $include_libraries = explode(',', UrlHelper::uncompressQueryParameter($request->query->get('include')));

    // Check that library names are in the correct format.
    $validate = function ($libraries_to_check) {
      foreach ($libraries_to_check as $library) {
        if (substr_count($library, '/') === 0) {
          throw new BadRequestHttpException(sprintf('The "%s" library name must include at least one slash.', $library));
        }
      }
    };
    $validate($include_libraries);
    $attached_assets->setLibraries($include_libraries);

    if ($request->query->has('exclude')) {
      $exclude_libraries = explode(',', UrlHelper::uncompressQueryParameter($request->query->get('exclude')));
      $validate($exclude_libraries);
      $attached_assets->setAlreadyLoadedLibraries($exclude_libraries);
    }
    $groups = $this->getGroups($attached_assets, $request);

    $group = $this->getGroup($groups, $request->query->get('delta'));
    // Generate a hash based on the asset group, this uses the same method as
    // the collection optimizer does to create the filename, so it should match.
    $generated_hash = $this->generateHash($group);
    $data = $this->optimizer->optimizeGroup($group);

    // However, the hash from the library definitions in code may not match the
    // hash from the URL. This can be for three reasons:
    // 1. Someone has requested an outdated URL, i.e. from a cached page, which
    // matches a different version of the code base.
    // 2. Someone has requested an outdated URL during a deployment. This is
    // the same case as #1 but a much shorter window.
    // 3. Someone is attempting to craft an invalid URL in order to conduct a
    // denial of service attack on the site.
    // Dump the optimized group into an aggregate file, but only if the
    // received hash and generated hash match. This prevents invalid filenames
    // from filling the disk, while still serving aggregates that may be
    // referenced in cached HTML.
    if (hash_equals($generated_hash, $received_hash)) {
      $this->dumper->dumpToUri($data, $this->assetType, $uri);
    }
    return new Response($data, 200, [
      'Cache-control' => static::CACHE_CONTROL,
      'Content-Type' => $this->contentType,
    ]);
  }

  /**
   * Gets a group.
   *
   * @param array $groups
   *   An array of asset groups.
   * @param int $group_delta
   *   The group delta.
   *
   * @return array
   *   The correct asset group matching $group_delta.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when the filename is invalid.
   */
  protected function getGroup(array $groups, int $group_delta): array {
    if (isset($groups[$group_delta])) {
      return $groups[$group_delta];
    }
    throw new BadRequestHttpException('Invalid filename.');
  }

  /**
   * Get grouped assets.
   *
   * @param \Drupal\Core\Asset\AttachedAssetsInterface $attached_assets
   *   The attached assets.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The grouped assets.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when the query argument is omitted.
   */
  abstract protected function getGroups(AttachedAssetsInterface $attached_assets, Request $request): array;

}
