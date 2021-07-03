<?php

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * @group taxonomy
 * @group legacy
 */
class TaxonomyDeprecationTest extends KernelTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter', 'taxonomy', 'text', 'user'];

  /**
   * @see taxonomy_vocabulary_get_names()
   * @see taxonomy_term_uri()
   * @see taxonomy_terms_static_reset()
   * @see taxonomy_vocabulary_static_reset()
   * @see taxonomy_implode_tags()
   * @see taxonomy_term_title()
   * @see drupal_static_reset()
   */
  public function testTaxonomyDeprecations(): void {
    $this->installEntitySchema('taxonomy_term');
    $vocabulary1 = $this->createVocabulary();
    $term1 = $this->createTerm($vocabulary1, ['name' => 'Foo']);
    $vocabulary2 = $this->createVocabulary();

    $this->expectDeprecation("taxonomy_vocabulary_get_names() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal::entityQuery('taxonomy_vocabulary')->execute() instead. See https://www.drupal.org/node/3039041");

    // Vocabulary names are keyed by machine name.
    $names = taxonomy_vocabulary_get_names();
    $this->assertCount(2, $names);
    $this->assertArrayHasKey($vocabulary1->id(), $names);
    $this->assertSame($vocabulary1->id(), $names[$vocabulary1->id()]);
    $this->assertArrayHasKey($vocabulary2->id(), $names);
    $this->assertSame($vocabulary2->id(), $names[$vocabulary2->id()]);

    $this->expectDeprecation('taxonomy_term_uri() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use $term->toUrl() instead. See https://www.drupal.org/node/3039041');
    $url = taxonomy_term_uri($term1);
    $this->assertEquals($term1->toUrl(), $url);

    $this->expectDeprecation("taxonomy_terms_static_reset() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal::entityTypeManager()->getStorage('taxonomy_term')->resetCache() instead. See https://www.drupal.org/node/3039041");
    taxonomy_terms_static_reset();

    $this->expectDeprecation('taxonomy_vocabulary_static_reset() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal::entityTypeManager()->getStorage("taxonomy_vocabulary")->resetCache($ids) instead. See https://www.drupal.org/node/3039041');
    taxonomy_vocabulary_static_reset();

    $this->expectDeprecation('taxonomy_implode_tags() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Entity\Element\EntityAutocomplete::getEntityLabels() instead. See https://www.drupal.org/node/3039041');
    taxonomy_implode_tags(['tag1', 'tag2']);

    $this->expectDeprecation('taxonomy_term_title() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use $term->label() instead. See https://www.drupal.org/node/3039041');
    $title = taxonomy_term_title($term1);
    $this->assertSame($term1->label(), $title);

    $this->expectDeprecation("Calling drupal_static_reset() with 'taxonomy_vocabulary_get_names' as argument is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There is no replacement for this usage. See https://www.drupal.org/node/3039041");
    drupal_static_reset('taxonomy_vocabulary_get_names');
  }

}
