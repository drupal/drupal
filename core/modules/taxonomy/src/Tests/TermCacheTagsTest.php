<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\TermCacheTagsTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\system\Tests\Entity\EntityWithUriCacheTagsTestBase;

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
    $vocabulary = entity_create('taxonomy_vocabulary',  array(
      'name' => 'Camelids',
      'vid' => 'camelids',
    ));
    $vocabulary->save();

    // Create a "Llama" taxonomy term.
    $term = entity_create('taxonomy_term', array(
      'name' => 'Llama',
      'vid' => $vocabulary->id(),
    ));
    $term->save();

    return $term;
  }

}
