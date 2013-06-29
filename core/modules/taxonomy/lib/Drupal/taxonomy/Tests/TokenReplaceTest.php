<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\TokenReplaceTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Language\Language;

/**
 * Test taxonomy token replacement in strings.
 */
class TokenReplaceTest extends TaxonomyTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy token replacement',
      'description' => 'Generates text using placeholders for dummy content to check taxonomy token replacement.',
      'group' => 'Taxonomy',
    );
  }

  function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy', 'bypass node access'));
    $this->drupalLogin($this->admin_user);
    $this->vocabulary = $this->createVocabulary();
    $this->langcode = Language::LANGCODE_NOT_SPECIFIED;
    $this->field_name = 'taxonomy_' . $this->vocabulary->id();
    entity_create('field_entity', array(
      'field_name' => $this->field_name,
      'type' => 'taxonomy_term_reference',
      'cardinality' => FIELD_CARDINALITY_UNLIMITED,
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $this->vocabulary->id(),
            'parent' => 0,
          ),
        ),
      ),
    ))->save();

    entity_create('field_instance', array(
      'field_name' => $this->field_name,
      'bundle' => 'article',
      'entity_type' => 'node',
    ))->save();
    entity_get_form_display('node', 'article', 'default')
      ->setComponent($this->field_name, array(
        'type' => 'options_select',
      ))
      ->save();
    entity_get_display('node', 'article', 'default')
      ->setComponent($this->field_name, array(
        'type' => 'taxonomy_term_reference_link',
      ))
      ->save();
  }

  /**
   * Creates some terms and a node, then tests the tokens generated from them.
   */
  function testTaxonomyTokenReplacement() {
    $token_service = \Drupal::token();
    $language_interface = language(Language::TYPE_INTERFACE);

    // Create two taxonomy terms.
    $term1 = $this->createTerm($this->vocabulary);
    $term2 = $this->createTerm($this->vocabulary);

    // Edit $term2, setting $term1 as parent.
    $edit = array();
    $edit['name'] = '<blink>Blinking Text</blink>';
    $edit['parent[]'] = array($term1->id());
    $this->drupalPost('taxonomy/term/' . $term2->id() . '/edit', $edit, t('Save'));

    // Create node with term2.
    $edit = array();
    $node = $this->drupalCreateNode(array('type' => 'article'));
    $edit[$this->field_name . '[' . $this->langcode . '][]'] = $term2->id();
    $this->drupalPost('node/' . $node->nid . '/edit', $edit, t('Save'));

    // Generate and test sanitized tokens for term1.
    $tests = array();
    $tests['[term:tid]'] = $term1->id();
    $tests['[term:name]'] = check_plain($term1->name->value);
    $tests['[term:description]'] = check_markup($term1->description->value, $term1->format->value);
    $tests['[term:url]'] = url('taxonomy/term/' . $term1->id(), array('absolute' => TRUE));
    $tests['[term:node-count]'] = 0;
    $tests['[term:parent:name]'] = '[term:parent:name]';
    $tests['[term:vocabulary:name]'] = check_plain($this->vocabulary->name);

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('term' => $term1), array('langcode' => $language_interface->id));
      $this->assertEqual($output, $expected, format_string('Sanitized taxonomy term token %token replaced.', array('%token' => $input)));
    }

    // Generate and test sanitized tokens for term2.
    $tests = array();
    $tests['[term:tid]'] = $term2->id();
    $tests['[term:name]'] = check_plain($term2->name->value);
    $tests['[term:description]'] = check_markup($term2->description->value, $term2->format->value);
    $tests['[term:url]'] = url('taxonomy/term/' . $term2->id(), array('absolute' => TRUE));
    $tests['[term:node-count]'] = 1;
    $tests['[term:parent:name]'] = check_plain($term1->name->value);
    $tests['[term:parent:url]'] = url('taxonomy/term/' . $term1->id(), array('absolute' => TRUE));
    $tests['[term:parent:parent:name]'] = '[term:parent:parent:name]';
    $tests['[term:vocabulary:name]'] = check_plain($this->vocabulary->name);

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('term' => $term2), array('langcode' => $language_interface->id));
      $this->assertEqual($output, $expected, format_string('Sanitized taxonomy term token %token replaced.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[term:name]'] = $term2->name->value;
    $tests['[term:description]'] = $term2->description->value;
    $tests['[term:parent:name]'] = $term1->name->value;
    $tests['[term:vocabulary:name]'] = $this->vocabulary->name;

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('term' => $term2), array('langcode' => $language_interface->id, 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, format_string('Unsanitized taxonomy term token %token replaced.', array('%token' => $input)));
    }

    // Generate and test sanitized tokens.
    $tests = array();
    $tests['[vocabulary:vid]'] = $this->vocabulary->id();
    $tests['[vocabulary:name]'] = check_plain($this->vocabulary->name);
    $tests['[vocabulary:description]'] = filter_xss($this->vocabulary->description);
    $tests['[vocabulary:node-count]'] = 1;
    $tests['[vocabulary:term-count]'] = 2;

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('vocabulary' => $this->vocabulary), array('langcode' => $language_interface->id));
      $this->assertEqual($output, $expected, format_string('Sanitized taxonomy vocabulary token %token replaced.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[vocabulary:name]'] = $this->vocabulary->name;
    $tests['[vocabulary:description]'] = $this->vocabulary->description;

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('vocabulary' => $this->vocabulary), array('langcode' => $language_interface->id, 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, format_string('Unsanitized taxonomy vocabulary token %token replaced.', array('%token' => $input)));
    }
  }
}
