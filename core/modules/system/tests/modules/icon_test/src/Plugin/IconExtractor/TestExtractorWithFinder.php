<?php

declare(strict_types=1);

namespace Drupal\icon_test\Plugin\IconExtractor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\Icon\Attribute\IconExtractor;
use Drupal\Core\Theme\Icon\IconExtractorWithFinder;
use Drupal\Core\Theme\Icon\IconPackExtractorForm;

/**
 * Test plugin implementation of the icon_extractor.
 */
#[IconExtractor(
  id: 'test_finder',
  label: new TranslatableMarkup('Test finder'),
  description: new TranslatableMarkup('Test extractor with files finder.'),
  forms: [
    'settings' => IconPackExtractorForm::class,
  ]
)]
class TestExtractorWithFinder extends IconExtractorWithFinder {

  /**
   * {@inheritdoc}
   */
  public function discoverIcons(): array {
    $files = $this->getFilesFromSources();
    $icons = [];
    foreach ($files as $file) {
      if (!isset($file['icon_id'])) {
        continue;
      }
      $icons[] = $this->createIcon($file['icon_id'], $file['source'], $file['group'] ?? NULL);
    }

    return $icons;
  }

}
