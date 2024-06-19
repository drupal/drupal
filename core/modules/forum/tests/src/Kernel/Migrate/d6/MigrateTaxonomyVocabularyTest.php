<?php

declare(strict_types=1);

namespace Drupal\Tests\forum\Kernel\Migrate\d6;

use Drupal\Tests\taxonomy\Kernel\Migrate\d6\MigrateTaxonomyVocabularyTest as TaxonomyVocabularyTest;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\VocabularyInterface;

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
    return __DIR__ . '/../../../../fixtures/drupal6.php';
  }

  /**
   * Validate a migrated vocabulary contains the expected values.
   *
   * @param string $id
   *   Entity ID to load and check.
   * @param $expected_label
   *   The label the migrated entity should have.
   * @param $expected_description
   *   The description the migrated entity should have.
   * @param $expected_weight
   *   The weight the migrated entity should have.
   *
   * @internal
   */
  protected function assertEntity(string $id, string $expected_label, string $expected_description, int $expected_weight): void {
    /** @var \Drupal\taxonomy\VocabularyInterface $entity */
    $entity = Vocabulary::load($id);
    $this->assertInstanceOf(VocabularyInterface::class, $entity);
    $this->assertSame($expected_label, $entity->label());
    $this->assertSame($expected_description, $entity->getDescription());
    $this->assertSame($expected_weight, (int) $entity->get('weight'));
  }

  /**
   * Tests the Drupal 6 taxonomy vocabularies migration.
   */
  public function testTaxonomyVocabulary(): void {
    $this->assertEntity('forums', 'Forums', '', 0);
    $this->assertEntity('trees', 'Trees', 'A list of trees.', 0);
    $this->assertEntity('freetags', 'FreeTags', '', 0);
  }

}
