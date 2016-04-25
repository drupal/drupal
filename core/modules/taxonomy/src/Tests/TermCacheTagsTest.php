<?php

namespace Drupal\taxonomy\Tests;

use Drupal\system\Tests\Entity\EntityWithUriCacheTagsTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

/**
 * Tests the Taxonomy term entity's cache tags.
 *
 * @group taxonomy
 */
class TermCacheTagsTest extends EntityWithUriCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('taxonomy');

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => 'Camelids',
      'vid' => 'camelids',
    ]);
    $vocabulary->save();

    // Create a "Llama" taxonomy term.
    $term = Term::create([
      'name' => 'Llama',
      'vid' => $vocabulary->id(),
    ]);
    $term->save();

    return $term;
  }

}
