<?php

declare(strict_types=1);

namespace Drupal\icon_test\Plugin\IconExtractor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\Icon\Attribute\IconExtractor;
use Drupal\Core\Theme\Icon\IconExtractorBase;
use Drupal\Core\Theme\Icon\IconPackExtractorForm;

/**
 * Test plugin implementation of the icon_extractor.
 */
#[IconExtractor(
  id: 'test',
  label: new TranslatableMarkup('Test'),
  description: new TranslatableMarkup('Test extractor.'),
  forms: [
    'settings' => IconPackExtractorForm::class,
  ]
)]
class TestExtractor extends IconExtractorBase {

  /**
   * {@inheritdoc}
   */
  public function discoverIcons(): array {
    return [];
  }

}
