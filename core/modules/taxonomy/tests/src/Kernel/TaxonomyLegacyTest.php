<?php

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests legacy user functionality.
 *
 * @group user
 * @group legacy
 */
class TaxonomyLegacyTest extends KernelTestBase {
  use TaxonomyTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'taxonomy', 'text', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['filter']);
    $this->installEntitySchema('taxonomy_term');
  }

  /**
   * @expectedDeprecation taxonomy_term_load_multiple() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\taxonomy\Entity\Term::loadMultiple(). See https://www.drupal.org/node/2266845
   * @expectedDeprecation taxonomy_vocabulary_load_multiple() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\taxonomy\Entity\Vocabulary::loadMultiple(). See https://www.drupal.org/node/2266845
   * @expectedDeprecation taxonomy_term_load() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\taxonomy\Entity\Term::load(). See https://www.drupal.org/node/2266845
   * @expectedDeprecation taxonomy_vocabulary_load() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\taxonomy\Entity\Vocabulary::load(). See https://www.drupal.org/node/2266845
   */
  public function testEntityLegacyCode() {
    $this->assertCount(0, taxonomy_term_load_multiple());
    $this->assertCount(0, taxonomy_vocabulary_load_multiple());
    $this->createTerm($this->createVocabulary());
    $this->assertCount(1, taxonomy_term_load_multiple());
    $this->assertCount(1, taxonomy_vocabulary_load_multiple());
    $vocab = $this->createVocabulary();
    $this->createTerm($vocab);
    $this->createTerm($vocab);
    $this->assertCount(3, taxonomy_term_load_multiple());
    $this->assertCount(2, taxonomy_vocabulary_load_multiple());

    $this->assertNull(taxonomy_term_load(3000));
    $this->assertInstanceOf(TermInterface::class, taxonomy_term_load(1));
    $this->assertNull(taxonomy_vocabulary_load('not_a_vocab'));
    $this->assertInstanceOf(VocabularyInterface::class, taxonomy_vocabulary_load($vocab->id()));
  }

  /**
   * @expectedDeprecation taxonomy_term_view() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal::entityTypeManager()->getViewBuilder('taxonomy_term')->view() instead. See https://www.drupal.org/node/3033656
   * @expectedDeprecation taxonomy_term_view_multiple() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal::entityTypeManager()->getViewBuilder('taxonomy_term')->viewMultiple() instead. See https://www.drupal.org/node/3033656
   */
  public function testTaxonomyTermView() {
    $entity = $this->createTerm($this->createVocabulary());
    $this->assertNotEmpty(taxonomy_term_view($entity));
    $entities = [
      $this->createTerm($this->createVocabulary()),
      $this->createTerm($this->createVocabulary()),
    ];
    $this->assertEquals(4, count(taxonomy_term_view_multiple($entities)));
  }

}
