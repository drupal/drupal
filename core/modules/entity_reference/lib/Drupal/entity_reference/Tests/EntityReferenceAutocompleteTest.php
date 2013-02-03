<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceAutocompleteTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\taxonomy\Tests\TaxonomyTestBase;

class EntityReferenceAutocompleteTest extends TaxonomyTestBase {

  public static $modules = array('entity_reference', 'taxonomy');

  public static function getInfo() {
    return array(
      'name' => 'Autocomplete',
      'description' => 'Tests autocomplete menu item.',
      'group' => 'Entity Reference',
    );
  }

  function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy', 'bypass node access'));
    $this->drupalLogin($this->admin_user);
    $this->vocabulary = $this->createVocabulary();

    $this->field_name = 'taxonomy_' . $this->vocabulary->id();

    $field = array(
      'field_name' => $this->field_name,
      'type' => 'entity_reference',
      'cardinality' => FIELD_CARDINALITY_UNLIMITED,
      'settings' => array(
        'target_type' => 'taxonomy_term',
      ),
    );
    field_create_field($field);

    $this->instance = array(
      'field_name' => $this->field_name,
      'bundle' => 'article',
      'entity_type' => 'node',
      'widget' => array(
        'type' => 'options_select',
      ),
      'settings' => array(
        'handler' => 'default',
        'handler_settings' => array(
          'target_bundles' => array(
            $this->vocabulary->id(),
          ),
          'auto_create' => TRUE,
        ),
      ),
    );
    field_create_instance($this->instance);
    entity_get_display('node', 'article', 'default')
      ->setComponent($this->instance['field_name'], array(
        'type' => 'entity_reference_label',
      ))
      ->save();
  }

  /**
   * Tests autocompletion edge cases with slashes in the names.
   */
  function testTermAutocompletion() {
    // Add a term with a slash in the name.
    $first_term = $this->createTerm($this->vocabulary);
    $first_term->name = '10/16/2011';
    taxonomy_term_save($first_term);
    // Add another term that differs after the slash character.
    $second_term = $this->createTerm($this->vocabulary);
    $second_term->name = '10/17/2011';
    taxonomy_term_save($second_term);
    // Add another term that has both a comma and a slash character.
    $third_term = $this->createTerm($this->vocabulary);
    $third_term->name = 'term with, a comma and / a slash';
    taxonomy_term_save($third_term);

    // Set the path prefix to point to entity reference's autocomplete path.
    $path_prefix = 'entity_reference/autocomplete/single/' . $this->field_name . '/node/article/NULL';

    // Try to autocomplete a term name that matches both terms.
    // We should get both terms in a JSON encoded string.
    $input = '10/';
    $result = $this->drupalGet($path_prefix, array('query' => array('q' => $input)));
    $data = drupal_json_decode($result);
    $this->assertEqual(strip_tags($data[$first_term->name. ' (1)']), check_plain($first_term->name), 'Autocomplete returned the first matching term');
    $this->assertEqual(strip_tags($data[$second_term->name. ' (2)']), check_plain($second_term->name), 'Autocomplete returned the second matching term');

    // Try to autocomplete a term name that matches the first term.
    // We should only get the first term in a JSON encoded string.
    $input = '10/16';
    $this->drupalGet($path_prefix, array('query' => array('q' => $input)));
    $target = array($first_term->name . ' (1)' => '<div class="reference-autocomplete">' . check_plain($first_term->name) . '</div>');
    $this->assertRaw(drupal_json_encode($target), 'Autocomplete returns only the expected matching term.');

    // Try to autocomplete a term name with both a comma and a slash.
    $input = '"term with, comma and / a';
    $this->drupalGet($path_prefix, array('query' => array('q' => $input)));
    $n = $third_term->name;
    // Term names containing commas or quotes must be wrapped in quotes.
    if (strpos($third_term->name, ',') !== FALSE || strpos($third_term->name, '"') !== FALSE) {
      $n = '"' . str_replace('"', '""', $third_term->name) .  ' (3)"';
    }
    $target = array($n => '<div class="reference-autocomplete">' . check_plain($third_term->name) . '</div>');
    $this->assertRaw(drupal_json_encode($target), 'Autocomplete returns a term containing a comma and a slash.');
  }
}
