<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Views\TaxonomyViewsFieldAccessTest.
 */

namespace Drupal\taxonomy\Tests\Views;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\User;
use Drupal\views\Tests\Handler\FieldFieldAccessTestBase;

/**
 * Tests base field access in Views for the taxonomy entity.
 *
 * @group taxonomy
 */
class TaxonomyViewsFieldAccessTest extends FieldFieldAccessTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy', 'text', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('taxonomy_term');
  }

  /**
   * Check access for taxonomy fields.
   */
  public function testTermFields() {
    $vocab = Vocabulary::create([
      'vid' => 'random',
      'name' => 'Randomness',
    ]);
    $vocab->save();
    $term1 = Term::create([
      'name' => 'Semi random',
      'vid' => $vocab->id(),
    ]);
    $term1->save();

    $term2 = Term::create([
      'name' => 'Majorly random',
      'vid' => $vocab->id(),
    ]);
    $term2->save();

    $term3 = Term::create([
      'name' => 'Not really random',
      'vid' => $vocab->id(),
    ]);
    $term3->save();

    $this->assertFieldAccess('taxonomy_term', 'name', 'Majorly random');
    $this->assertFieldAccess('taxonomy_term', 'name', 'Semi random');
    $this->assertFieldAccess('taxonomy_term', 'name', 'Not really random');
    $this->assertFieldAccess('taxonomy_term', 'tid', $term1->id());
    $this->assertFieldAccess('taxonomy_term', 'tid', $term2->id());
    $this->assertFieldAccess('taxonomy_term', 'tid', $term3->id());
    $this->assertFieldAccess('taxonomy_term', 'uuid', $term1->uuid());
    $this->assertFieldAccess('taxonomy_term', 'uuid', $term2->uuid());
    $this->assertFieldAccess('taxonomy_term', 'uuid', $term3->uuid());
  }

}
