<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Views\TaxonomyTestBase.
 */

namespace Drupal\taxonomy\Tests\Views;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Language\Language;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Base class for all taxonomy tests.
 */
abstract class TaxonomyTestBase extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'taxonomy_test_views');

  /**
   * Stores the nodes used for the different tests.
   *
   * @var array
   */
  protected $nodes = array();

  /**
   * Stores the first term used in the different tests.
   *
   * @var \Drupal\taxonomy\Term
   */
  protected $term1;

  /**
   * Stores the second term used in the different tests.
   *
   * @var \Drupal\taxonomy\Term
   */
  protected $term2;

  function setUp() {
    parent::setUp();
    $this->mockStandardInstall();

    ViewTestData::createTestViews(get_class($this), array('taxonomy_test_views'));

    $this->term1 = $this->createTerm();
    $this->term2 = $this->createTerm();

    $node = array();
    $node['type'] = 'article';
    $node['field_views_testing_tags'][]['target_id'] = $this->term1->id();
    $node['field_views_testing_tags'][]['target_id'] = $this->term2->id();
    $this->nodes[] = $this->drupalCreateNode($node);
    $this->nodes[] = $this->drupalCreateNode($node);
  }

  /**
   * Provides a workaround for the inability to use the standard profile.
   *
   * @see http://drupal.org/node/1708692
   */
  protected function mockStandardInstall() {
    $this->drupalCreateContentType(array(
      'type' => 'article',
    ));
    // Create the vocabulary for the tag field.
    $this->vocabulary = entity_create('taxonomy_vocabulary',  array(
      'name' => 'Views testing tags',
      'vid' => 'views_testing_tags',
    ));
    $this->vocabulary->save();
    $this->field_name = 'field_' . $this->vocabulary->id();
    entity_create('field_config', array(
      'name' => $this->field_name,
      'entity_type' => 'node',
      'type' => 'taxonomy_term_reference',
      // Set cardinality to unlimited for tagging.
      'cardinality' => FieldDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $this->vocabulary->id(),
            'parent' => 0,
          ),
        ),
      ),
    ))->save();
    entity_create('field_instance_config', array(
      'field_name' => $this->field_name,
      'entity_type' => 'node',
      'label' => 'Tags',
      'bundle' => 'article',
    ))->save();

    entity_get_form_display('node', 'article', 'default')
      ->setComponent($this->field_name, array(
        'type' => 'taxonomy_autocomplete',
        'weight' => -4,
      ))
      ->save();

    entity_get_display('node', 'article', 'default')
      ->setComponent($this->field_name, array(
        'type' => 'taxonomy_term_reference_link',
        'weight' => 10,
      ))
      ->save();
    entity_get_display('node', 'article', 'teaser')
      ->setComponent($this->field_name, array(
        'type' => 'taxonomy_term_reference_link',
        'weight' => 10,
      ))
      ->save();
  }

  /**
   * Returns a new term with random properties in vocabulary $vid.
   *
   * @return \Drupal\taxonomy\Term
   *   The created taxonomy term.
   */
  protected function createTerm() {
    $filter_formats = filter_formats();
    $format = array_pop($filter_formats);
    $term = entity_create('taxonomy_term', array(
      'name' => $this->randomName(),
      'description' => $this->randomName(),
      // Use the first available text format.
      'format' => $format->format,
      'vid' => $this->vocabulary->id(),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ));
    $term->save();
    return $term;
  }

}
