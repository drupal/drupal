<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Icon;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

/**
 * Icon finder to discover files under specific paths or URLs.
 *
 * This class locates icon files based on a provided source, which can be either
 * a local path with optional pattern to extract id and group or an URL.
 *
 * Local Paths:
 * For local paths, the class leverage Symfony Finder features with some extra
 * functionalities related to our icon definition:
 * - Icon ID Extraction (`{icon_id}`): A placeholder `{icon_id}` within
 *   the filename allows extracting a portion as the icon ID. For
 *   example, a source definition like `/{icon_id}-24.svg` would extract
 *   "book" as the icon ID from the file "book-24.svg".
 * - Group Metadata Extraction (`{group}`): A placeholder `{group}` within
 *   the path allows extracting a folder name as group metadata for the icon.
 *   For instance, a source definition like `/foo/{group}/*` for the file
 *   "foo/outline/icon.svg" would assign "outline" as the group for the icon.
 * The source path can be:
 * - Absolute: Starting with a slash `/`, indicating a path relative to the
 *   Drupal installation root.
 * - Relative: Without a leading slash, indicating a path relative to the
 *   definition folder.
 * URLs:
 * For URLs, the source is treated as the direct URL to the icon resource.
 * Patterns can not be applied so the filename will be used as icon_id and no
 * group is possible.
 * Query parameters that change the file display are not supported as icon_id is
 * based on the resource filename through pathinfo().
 *
 * The class returns an array containing information about the discovered icon:
 * - icon_id (string)
 *   Id based on filename or {icon_id} pattern for path
 * - source (string)
 *   URL to the file, can be external or internal to the Drupal
 * - absolute_path (string)
 *   Local path to the file or url, some extractors may need to read the file
 * - group (string|null)
 *   Optional metadata extracted from {group} pattern if used in the source
 *
 * When multiple icons with the same filename (or icon_id) are discovered for
 * the same icon pack, only the last one in definition order will be kept.
 * The icon_id does not include the extension of the file or any query
 * parameters.
 * This is intentional to allow moving icons in different folders and even
 * switching format while keeping the same id.
 *
 * In the same way, the filename is used as icon_id without transformation and
 * could contain special characters.
 *
 * @internal
 *   This API is experimental.
 */
class IconFinder implements ContainerInjectionInterface, IconFinderInterface {

  use AutowireTrait;

  /**
   * Pattern to match a group placeholder in a source path.
   *
   * This constant is used to identify and extract group metadata from source
   * paths defined for icon pack.
   */
  private const GROUP_PATTERN = '{group}';

  /**
   * Pattern to match an icon ID placeholder in a filename.
   *
   * This constant is used to identify and extract icon IDs from filenames
   * within source paths defined for icon pack.
   */
  private const ICON_ID_PATTERN = '{icon_id}';

  /**
   * List of allowed file extensions for local icon files.
   *
   * This restriction is in place for security reasons, so a definition can not
   * be used to expose the content of a non image to the extractor.
   * Furthermore it make no sense to use other file format as Icons.
   */
  private const ALLOWED_EXTENSION = ['svg', 'png', 'gif'];

  /**
   * Constructs a new IconFinder object.
   *
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param string $appRoot
   *   The application root.
   */
  public function __construct(
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
    private readonly LoggerInterface $logger,
    private readonly string $appRoot,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getFileContents(string $uri): string|bool {
    $url = parse_url($uri);
    if (isset($url['scheme']) || isset($url['host'])) {
      return FALSE;
    }
    if (!file_exists($uri)) {
      return FALSE;
    }
    return file_get_contents($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function getFilesFromSources(array $sources, string $relative_path): array {
    $result = [];
    foreach ($sources as $source) {
      // Detect if source is path or remote, parse_url will have no scheme for
      // a path.
      $url = parse_url($source);
      if (isset($url['scheme']) && isset($url['path'])) {
        $result = array_merge($result, $this->getFileFromUrl($url['scheme'], $url['path'], $source));
        continue;
      }
      $result = array_merge($result, $this->getFilesFromPath($source, $relative_path));
    }

    return $result;
  }

  /**
   * Get filename from an URL source.
   *
   * Because url to an image can be of various form, there is no extension
   * validation, only scheme, the extractor must handle the constraints.
   *
   * @param string $scheme
   *   The url scheme from parse_url().
   * @param string $path
   *   The url path from parse_url().
   * @param string $source
   *   The source.
   *
   * @return array<string, array<string, string|null>>
   *   The file discovered.
   */
  private function getFileFromUrl(string $scheme, string $path, string $source): array {
    if (!in_array($scheme, UrlHelper::getAllowedProtocols(), TRUE)) {
      $this->logger->warning('Invalid icon source: @source', ['@source' => $source]);
      return [];
    }

    // Decode url to have cleaner filename.
    $icon_id = pathinfo(urldecode($path), PATHINFO_FILENAME);

    // Icon ID is used as index to avoid duplicates.
    return [
      $icon_id => [
        'icon_id' => $icon_id,
        'source' => $source,
        'absolute_path' => $source,
      ],
    ];
  }

  /**
   * Get files from a local path.
   *
   * This is a wrapper to use Symfony Finder with 2 extras features {group} and
   * {icon_id}.
   *
   * @param string $source
   *   The source path, which can be absolute (starting with '/') or relative
   *   to the definition folder.
   * @param string $relative_path
   *   The relative path to the definition folder.
   *
   * @return array<string, array<string, string|null>>
   *   The file discovered.
   */
  private function getFilesFromPath(string $source, string $relative_path): array {
    $path_info = pathinfo($source);
    $dirname = $path_info['dirname'] ?? '';
    $extension = $path_info['extension'] ?? '';
    $filename = $path_info['filename'] ?? '';

    if (empty($dirname)) {
      return [];
    }

    // Set extension to wildcard if empty, and validate against allowed
    // extensions.
    $extension = empty($extension) ? '*' : $extension;
    if ('*' !== $extension && !in_array($extension, self::ALLOWED_EXTENSION, TRUE)) {
      $this->logger->warning('Invalid icon path extension @filename.@extension in source: @source', [
        '@filename' => $filename,
        '@extension' => $extension,
        '@source' => $source,
      ]);
      return [];
    }

    // Use allowed extension bracket for Finder if wildcard.
    $extension = '*' === $extension ? '{' . implode(',', self::ALLOWED_EXTENSION) . '}' : $extension;

    // Prepare filename wildcard if empty or with {icon_id} pattern.
    $filename_wildcard = empty($filename) ? '*' : str_replace(self::ICON_ID_PATTERN, '*', $filename);

    // If icons are in the same folder dirname is 'dot'.
    if ('.' === $dirname) {
      $dirname = '';
    }

    // Prepare path to search for icons for Finder::in().
    $path = str_starts_with($source, '/') ?
      $this->appRoot . $dirname :
      sprintf('%s/%s/%s', $this->appRoot, $relative_path, $dirname);

    // Prepare file names for Finder::name().
    $names = sprintf('%s.%s', $filename_wildcard, $extension);

    if (!$finder = $this->findFiles($path, $names)) {
      return [];
    }

    // Wildcard around filename are ignored for extractIconIdFromFilename.
    $filename = str_replace('*', '', $filename);

    return $this->processFoundFiles($finder, $source, $filename, self::determineGroupPosition($path));
  }

  /**
   * Creates a Finder instance with configured patterns and return result.
   *
   * @param string $path
   *   The path to search for icons.
   * @param string $names
   *   The file names for Finder::names().
   *
   * @return \Symfony\Component\Finder\Finder|null
   *   The configured Finder instance.
   */
  private function findFiles(string $path, string $names): ?Finder {
    $path = str_replace(self::GROUP_PATTERN, '*', $path);
    $finder = new Finder();
    try {
      $finder
        ->depth(0)
        ->in($path)
        ->files()
        ->name($names)
        ->sortByExtension();
    }
    catch (\Throwable) {
      $this->logger->warning('Invalid icon path in source: @source', ['@source' => $path]);
      return NULL;
    }

    if (!$finder->hasResults()) {
      $this->logger->warning('No icon found in source: @source', ['@source' => $path]);
      return NULL;
    }

    return $finder;
  }

  /**
   * Process files and format icon information.
   *
   * @param \Symfony\Component\Finder\Finder $finder
   *   The Finder instance with found files.
   * @param string $source
   *   The source.
   * @param string $path_info_filename
   *   The filename from path_info().
   * @param int|null $group_position
   *   The position of the group in the path, or null if not applicable.
   *
   * @return array<string, array<string, string|null>>
   *   List of files with metadata.
   */
  private function processFoundFiles(Finder $finder, string $source, string $path_info_filename, ?int $group_position): array {
    $result = [];
    $has_icon_pattern = \str_contains($path_info_filename, self::ICON_ID_PATTERN);

    foreach ($finder as $file) {
      $file_absolute_path = $file->getPathName();
      $icon_id = $file->getFilenameWithoutExtension();

      // If an {icon_id} pattern is used, extract it to be used.
      if ($has_icon_pattern) {
        $icon_id = self::extractIconIdFromFilename($icon_id, $path_info_filename);
      }

      // Icon ID is used as index to avoid duplicates.
      $result[$icon_id] = [
        'icon_id' => $icon_id,
        'source' => $this->fileUrlGenerator->generateString(str_replace($this->appRoot, '', $file_absolute_path)),
        'absolute_path' => $file_absolute_path,
        'group' => self::extractGroupFromPath($file->getPath(), $group_position),
      ];
    }

    return $result;
  }

  /**
   * Check if {icon_id} is a part of the name and need to be extracted.
   *
   * @param string $filename
   *   The filename found to match against.
   * @param string $filename_pattern
   *   The path with {icon_id}.
   *
   * @return string
   *   The extracted icon ID or the original filename.
   */
  private static function extractIconIdFromFilename(string $filename, string $filename_pattern): string {
    $pattern = str_replace(self::ICON_ID_PATTERN, '(?<icon_id>.+)?', $filename_pattern);
    if (preg_match('@' . $pattern . '@', $filename, $matches)) {
      return $matches['icon_id'] ?? $filename;
    }

    return $filename;
  }

  /**
   * Extracts the group from a file path based on the group position.
   *
   * @param string $path
   *   The file path.
   * @param int|null $group_position
   *   The position of the group in the path, or null if not applicable.
   *
   * @return string|null
   *   The extracted group, or null if not found.
   */
  private static function extractGroupFromPath(string $path, ?int $group_position): ?string {
    $parts = explode('/', trim($path, '/'));
    return $parts[$group_position] ?? NULL;
  }

  /**
   * Determines the group position based on the URI.
   *
   * The group position is needed to use it as a mask to find the folder name
   * matching this position.
   *
   * @param string $source
   *   The source with optional group pattern.
   *
   * @return int|null
   *   The determined group position.
   */
  private static function determineGroupPosition(string $source): ?int {
    $parts = explode('/', trim($source, '/'));
    if ($result = array_search(self::GROUP_PATTERN, $parts, TRUE)) {
      return (int) $result;
    }

    return NULL;
  }

}
