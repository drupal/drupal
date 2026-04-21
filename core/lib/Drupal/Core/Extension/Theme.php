<?php

declare(strict_types=1);

namespace Drupal\Core\Extension;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The Theme extension object.
 *
 * Extending this class is not supported and no BC is provided for subclasses.
 *
 * @see \Drupal\Core\Extension\ThemeExtensionList::doList()
 *
 * @todo https://www.drupal.org/project/drupal/issues/3026232 Replace public
 *   and dynamic properties with methods.
 *
 * @final
 */
class Theme extends Extension {

  /**
   * Constructs a new Theme object.
   *
   * @param string $root
   *   The app root.
   * @param string $pathname
   *   The relative path and filename of the extension's info file; e.g.,
   *   'core/themes/olivero/olivero.info.yml'.
   * @param array $info
   *   The info array parsed from the theme's .info.yml file.
   * @param string|null $filename
   *   (optional) The filename of the main extension file; e.g., olivero.theme.
   */
  public function __construct(string $root, string $pathname, array $info, ?string $filename = NULL) {
    parent::__construct($root, 'theme', $pathname, $filename);
    $this->info = $info;
  }

  /**
   * Lists all the theme's regions.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   An array of human-readable region names keyed by machine names.
   */
  public function listAllRegions(): array {
    return array_map(static function ($label) {
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      return new TranslatableMarkup($label);
    }, $this->info['regions']);
  }

  /**
   * Lists all the theme's visible regions.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   An array of human-readable region names keyed by machine names.
   */
  public function listVisibleRegions(): array {
    // List only regions that do not appear in the 'regions_hidden' key.
    return array_diff_key(
      $this->listAllRegions(),
      array_flip($this->info['regions_hidden'])
    );
  }

  /**
   * Gets the name of the default region for the theme.
   *
   * @return string
   *   The machine name of the default region.
   */
  public function getDefaultRegion(): string {
    return (string) key($this->listVisibleRegions());
  }

}
