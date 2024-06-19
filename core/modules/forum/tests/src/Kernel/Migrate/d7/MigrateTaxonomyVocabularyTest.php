<?php

declare(strict_types=1);

namespace Drupal\Tests\forum\Kernel\Migrate\d7;

use Drupal\Tests\taxonomy\Kernel\Migrate\d7\MigrateTaxonomyVocabularyTest as TaxonomyVocabularyTest;

/**
 * Migrate forum vocabulary to taxonomy.vocabulary.*.yml.
 *
 * @group forum
 */
class MigrateTaxonomyVocabularyTest extends TaxonomyVocabularyTest {

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
   * Tests the Drupal 7 taxonomy vocabularies to Drupal 8 migration.
   */
  public function testTaxonomyVocabulary(): void {
    $this->assertEntity('tags', 'Tags', 'Use tags to group articles on similar topics into categories.', 0);
    $this->assertEntity('forums', 'Subject of discussion', 'Forum navigation vocabulary', -10);
  }

}
