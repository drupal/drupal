<?php

declare(strict_types=1);

namespace Drupal\Tests\forum\Kernel\Migrate\d7;

use Drupal\Tests\taxonomy\Kernel\Migrate\d7\MigrateTaxonomyTermTranslationTest as TaxonomyTermTranslationTest;

/**
 * Test migration of translated taxonomy terms.
 *
 * @group forum
 */
class MigrateTaxonomyTermTranslationTest extends TaxonomyTermTranslationTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'forum',
  ];

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../../fixtures/drupal7.php';
  }

  /**
   * Tests the Drupal i18n  taxonomy term to Drupal 8 migration.
   */
  public function testTaxonomyTermTranslation(): void {
    // Forums vocabulary, no multilingual option.
    $this->assertEntity(1, 'en', 'General discussion', 'forums', NULL, NULL, 2, []);
    $this->assertEntity(5, 'en', 'Custom Forum', 'forums', 'Where the cool kids are.', NULL, 3, []);
    $this->assertEntity(6, 'en', 'Games', 'forums', NULL, NULL, 4, []);
    $this->assertEntity(7, 'en', 'Minecraft', 'forums', NULL, NULL, 1, ['6']);
    $this->assertEntity(8, 'en', 'Half Life 3', 'forums', NULL, NULL, 0, ['6']);
  }

}
