<?php

declare(strict_types=1);

namespace Drupal\Tests\forum\Kernel\Migrate\d7;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\language\Kernel\Migrate\d7\MigrateLanguageContentTaxonomyVocabularySettingsTest as CoreTest;

/**
 * Tests migration of i18ntaxonomy vocabulary settings.
 *
 * @group forum
 */
class MigrateLanguageContentTaxonomyVocabularySettingsTest extends CoreTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'forum',
  ];

  /**
   * Tests migration of 18ntaxonomy vocabulary settings.
   */
  public function testLanguageContentTaxonomy(): void {
    $this->assertLanguageContentSettings('taxonomy_term', 'forums', LanguageInterface::LANGCODE_NOT_SPECIFIED, FALSE, ['enabled' => FALSE]);
  }

}
