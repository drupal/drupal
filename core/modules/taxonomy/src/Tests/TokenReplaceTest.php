<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\TokenReplaceTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Generates text using placeholders for dummy content to check taxonomy token
 * replacement.
 *
 * @group taxonomy
 */
class TokenReplaceTest extends TaxonomyTestBase {

  protected function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy', 'bypass node access'));
    $this->drupalLogin($this->admin_user);
    $this->vocabulary = $this->createVocabulary();
    $this->field_name = 'taxonomy_' . $this->vocabulary->id();
    entity_create('field_storage_config', array(
      'name' => $this->field_name,
      'entity_type' => 'node',
      'type' => 'taxonomy_term_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $this->vocabulary->id(),
            'parent' => 0,
          ),
        ),
      ),
    ))->save();

    entity_create('field_config', array(
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
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();

    // Create two taxonomy terms.
    $term1 = $this->createTerm($this->vocabulary);
    $term2 = $this->createTerm($this->vocabulary);

    // Edit $term2, setting $term1 as parent.
    $edit = array();
    $edit['name[0][value]'] = '<blink>Blinking Text</blink>';
    $edit['parent[]'] = array($term1->id());
    $this->drupalPostForm('taxonomy/term/' . $term2->id() . '/edit', $edit, t('Save'));

    // Create node with term2.
    $edit = array();
    $node = $this->drupalCreateNode(array('type' => 'article'));
    $edit[$this->field_name . '[]'] = $term2->id();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    // Generate and test sanitized tokens for term1.
    $tests = array();
    $tests['[term:tid]'] = $term1->id();
    $tests['[term:name]'] = String::checkPlain($term1->getName());
    $tests['[term:description]'] = $term1->description->processed;
    $tests['[term:url]'] = $term1->url('canonical', array('absolute' => TRUE));
    $tests['[term:node-count]'] = 0;
    $tests['[term:parent:name]'] = '[term:parent:name]';
    $tests['[term:vocabulary:name]'] = String::checkPlain($this->vocabulary->name);

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('term' => $term1), array('langcode' => $language_interface->id));
      $this->assertEqual($output, $expected, format_string('Sanitized taxonomy term token %token replaced.', array('%token' => $input)));
    }

    // Generate and test sanitized tokens for term2.
    $tests = array();
    $tests['[term:tid]'] = $term2->id();
    $tests['[term:name]'] = String::checkPlain($term2->getName());
    $tests['[term:description]'] = $term2->description->processed;
    $tests['[term:url]'] = $term2->url('canonical', array('absolute' => TRUE));
    $tests['[term:node-count]'] = 1;
    $tests['[term:parent:name]'] = String::checkPlain($term1->getName());
    $tests['[term:parent:url]'] = $term1->url('canonical', array('absolute' => TRUE));
    $tests['[term:parent:parent:name]'] = '[term:parent:parent:name]';
    $tests['[term:vocabulary:name]'] = String::checkPlain($this->vocabulary->name);

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('term' => $term2), array('langcode' => $language_interface->id));
      $this->assertEqual($output, $expected, format_string('Sanitized taxonomy term token %token replaced.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[term:name]'] = $term2->getName();
    $tests['[term:description]'] = $term2->getDescription();
    $tests['[term:parent:name]'] = $term1->getName();
    $tests['[term:vocabulary:name]'] = $this->vocabulary->name;

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('term' => $term2), array('langcode' => $language_interface->id, 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, format_string('Unsanitized taxonomy term token %token replaced.', array('%token' => $input)));
    }

    // Generate and test sanitized tokens.
    $tests = array();
    $tests['[vocabulary:vid]'] = $this->vocabulary->id();
    $tests['[vocabulary:name]'] = String::checkPlain($this->vocabulary->name);
    $tests['[vocabulary:description]'] = Xss::filter($this->vocabulary->description);
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

