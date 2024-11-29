<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Plugin\IconExtractor;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Theme\Icon\Attribute\IconExtractor;
use Drupal\Core\Theme\Icon\IconDefinition;
use Drupal\Core\Theme\Icon\IconDefinitionInterface;
use Drupal\Core\Theme\Icon\IconExtractorWithFinder;
use Drupal\Core\Theme\Icon\IconPackExtractorForm;

/**
 * Plugin implementation of the icon_extractor.
 *
 * This extractor needs the file content, only local SVG are allowed to avoid
 * any security risk. For remote sources, `path` extractor must be used or
 * `svg_sprite` for remote sprite.
 *
 * @internal
 *   This API is experimental.
 */
#[IconExtractor(
  id: 'svg',
  label: new TranslatableMarkup('SVG'),
  description: new TranslatableMarkup('Handles SVG files from one or many paths, remote is not allowed and will be ignored.'),
  forms: [
    'settings' => IconPackExtractorForm::class,
  ]
)]
class SvgExtractor extends IconExtractorWithFinder {

  /**
   * {@inheritdoc}
   */
  public function discoverIcons(): array {
    // Check is included in getFilesFromSources(), but we need to disallow
    // remote sources before.
    $this->checkRequiredConfigSources();

    $this->configuration['config']['sources'] = array_filter($this->configuration['config']['sources'], function ($source) {
      return empty(parse_url($source, PHP_URL_SCHEME));
    });

    if (empty($this->configuration['config']['sources'])) {
      return [];
    }

    $files = $this->getFilesFromSources();

    if (empty($files)) {
      return [];
    }

    $icons = [];
    foreach ($files as $file) {
      if (!isset($file['absolute_path']) || empty($file['absolute_path'])) {
        continue;
      }

      $id = IconDefinition::createIconId($this->configuration['id'], $file['icon_id']);
      $icons[$id] = [
        'absolute_path' => $file['absolute_path'],
        'source' => $file['source'],
        'group' => $file['group'] ?? NULL,
      ];
    }

    return $icons;
  }

  /**
   * {@inheritdoc}
   */
  public function loadIcon(array $icon_data): ?IconDefinitionInterface {
    if (!isset($icon_data['icon_id']) || !isset($icon_data['source']) || !isset($icon_data['absolute_path'])) {
      return NULL;
    }

    if (!$svg_data = $this->extractSvg($icon_data['absolute_path'])) {
      return NULL;
    }

    return $this->createIcon(
      $icon_data['icon_id'],
      $icon_data['source'],
      $icon_data['group'] ?? NULL,
      $svg_data,
    );
  }

  /**
   * Extract svg values, simply exclude parent <svg>.
   *
   * @param string $source
   *   Local path or url to the svg file.
   *
   * @return array|null
   *   The SVG `content` as string and `viewbox` value if any.
   */
  private function extractSvg(string $source): ?array {
    if (!$content = $this->iconFinder->getFileContents($source)) {
      return NULL;
    }

    libxml_use_internal_errors(TRUE);

    if (!$svg = simplexml_load_string((string) $content)) {
      // @todo do we need to log a warning with the xml error?
      return NULL;
    }

    $return = [
      'content' => '',
      'attributes' => new Attribute(),
    ];
    foreach ($svg as $child) {
      $return['content'] .= $child->asXML();
    }

    if (empty($return['content'])) {
      return NULL;
    }

    // Content contain xml data and will be printed, we need to not escape it
    // for rendering.
    $return['content'] = new FormattableMarkup($return['content'], []);

    // Add svg attributes to be available in the template.
    foreach ($svg->attributes() as $name => $value) {
      $return['attributes']->setAttribute($name, (string) $value);
    }

    return $return;
  }

}
