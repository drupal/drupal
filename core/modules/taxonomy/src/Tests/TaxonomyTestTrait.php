<?php

namespace Drupal\taxonomy\Tests;

@trigger_error(__NAMESPACE__ . '\TaxonomyTestTrait is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait instead', E_USER_DEPRECATED);

use Drupal\Core\Language\LanguageInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides common helper methods for Taxonomy module tests.
 *
 * @deprecated in drupal:8.4.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait instead.
 */
trait TaxonomyTestTrait {

  /**
   * Returns a new vocabulary with random properties.
   */
  public function createVocabulary() {
    // Create a vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => mb_strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'weight' => mt_rand(0, 10),
    ]);
    $vocabulary->save();
    return $vocabulary;
  }

  /**
   * Returns a new term with random properties in vocabulary $vid.
   *
   * @param \Drupal\taxonomy\Entity\Vocabulary $vocabulary
   *   The vocabulary object.
   * @param array $values
   *   (optional) An array of values to set, keyed by property name. If the
   *   entity type has bundles, the bundle key has to be specified.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The new taxonomy term object.
   */
  public function createTerm(Vocabulary $vocabulary, $values = []) {
    $filter_formats = filter_formats();
    $format = array_pop($filter_formats);
    $term = Term::create($values + [
      'name' => $this->randomMachineName(),
      'description' => [
        'value' => $this->randomMachineName(),
        // Use the first available text format.
        'format' => $format->id(),
      ],
      'vid' => $vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $term->save();
    return $term;
  }

}
