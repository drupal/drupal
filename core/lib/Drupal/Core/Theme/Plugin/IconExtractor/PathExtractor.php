<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Plugin\IconExtractor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\Icon\Attribute\IconExtractor;
use Drupal\Core\Theme\Icon\IconDefinition;
use Drupal\Core\Theme\Icon\IconExtractorWithFinder;
use Drupal\Core\Theme\Icon\IconPackExtractorForm;

/**
 * Plugin implementation of the icon_extractor.
 *
 * @internal
 *   This API is experimental.
 */
#[IconExtractor(
  id: 'path',
  label: new TranslatableMarkup('Path or URL'),
  description: new TranslatableMarkup('Handles paths or URLs for icons.'),
  forms: [
    'settings' => IconPackExtractorForm::class,
  ]
)]
class PathExtractor extends IconExtractorWithFinder {

  /**
   * {@inheritdoc}
   */
  public function discoverIcons(): array {
    $files = $this->getFilesFromSources();

    if (empty($files)) {
      return [];
    }

    $icons = [];
    foreach ($files as $file) {
      $id = IconDefinition::createIconId($this->configuration['id'], $file['icon_id']);
      $icons[$id] = [
        'absolute_path' => $file['absolute_path'],
        'source' => $file['source'],
        'group' => $file['group'] ?? NULL,
      ];
    }

    return $icons;
  }

}
