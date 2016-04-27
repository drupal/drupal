<?php

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Generates text using placeholders for dummy content to check taxonomy token
 * replacement.
 *
 * @group taxonomy
 */
class TokenReplaceTest extends TaxonomyTestBase {

  /**
   * The vocabulary used for creating terms.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Name of the taxonomy term reference field.
   *
   * @var string
   */
  protected $fieldName;

  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['administer taxonomy', 'bypass node access']));
    $this->vocabulary = $this->createVocabulary();
    $this->fieldName = 'taxonomy_' . $this->vocabulary->id();

    $handler_settings = array(
      'target_bundles' => array(
        $this->vocabulary->id() => $this->vocabulary->id(),
      ),
      'auto_create' => TRUE,
    );
    $this->createEntityReferenceField('node', 'article', $this->fieldName, NULL, 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    entity_get_form_display('node', 'article', 'default')
      ->setComponent($this->fieldName, array(
        'type' => 'options_select',
      ))
      ->save();
    entity_get_display('node', 'article', 'default')
      ->setComponent($this->fieldName, array(
        'type' => 'entity_reference_label',
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
    $edit[$this->fieldName . '[]'] = $term2->id();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    // Generate and test sanitized tokens for term1.
    $tests = array();
    $tests['[term:tid]'] = $term1->id();
    $tests['[term:name]'] = $term1->getName();
    $tests['[term:description]'] = $term1->description->processed;
    $tests['[term:url]'] = $term1->url('canonical', array('absolute' => TRUE));
    $tests['[term:node-count]'] = 0;
    $tests['[term:parent:name]'] = '[term:parent:name]';
    $tests['[term:vocabulary:name]'] = $this->vocabulary->label();
    $tests['[term:vocabulary]'] = $this->vocabulary->label();

    $base_bubbleable_metadata = BubbleableMetadata::createFromObject($term1);

    $metadata_tests = array();
    $metadata_tests['[term:tid]'] = $base_bubbleable_metadata;
    $metadata_tests['[term:name]'] = $base_bubbleable_metadata;
    $metadata_tests['[term:description]'] = $base_bubbleable_metadata;
    $metadata_tests['[term:url]'] = $base_bubbleable_metadata;
    $metadata_tests['[term:node-count]'] = $base_bubbleable_metadata;
    $metadata_tests['[term:parent:name]'] = $base_bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[term:vocabulary:name]'] = $bubbleable_metadata->addCacheTags($this->vocabulary->getCacheTags());
    $metadata_tests['[term:vocabulary]'] = $bubbleable_metadata->addCacheTags($this->vocabulary->getCacheTags());

    foreach ($tests as $input => $expected) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $token_service->replace($input, array('term' => $term1), array('langcode' => $language_interface->getId()), $bubbleable_metadata);
      $this->assertEqual($output, $expected, format_string('Sanitized taxonomy term token %token replaced.', array('%token' => $input)));
      $this->assertEqual($bubbleable_metadata, $metadata_tests[$input]);
    }

    // Generate and test sanitized tokens for term2.
    $tests = array();
    $tests['[term:tid]'] = $term2->id();
    $tests['[term:name]'] = $term2->getName();
    $tests['[term:description]'] = $term2->description->processed;
    $tests['[term:url]'] = $term2->url('canonical', array('absolute' => TRUE));
    $tests['[term:node-count]'] = 1;
    $tests['[term:parent:name]'] = $term1->getName();
    $tests['[term:parent:url]'] = $term1->url('canonical', array('absolute' => TRUE));
    $tests['[term:parent:parent:name]'] = '[term:parent:parent:name]';
    $tests['[term:vocabulary:name]'] = $this->vocabulary->label();

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('term' => $term2), array('langcode' => $language_interface->getId()));
      $this->assertEqual($output, $expected, format_string('Sanitized taxonomy term token %token replaced.', array('%token' => $input)));
    }

    // Generate and test sanitized tokens.
    $tests = array();
    $tests['[vocabulary:vid]'] = $this->vocabulary->id();
    $tests['[vocabulary:name]'] = $this->vocabulary->label();
    $tests['[vocabulary:description]'] = $this->vocabulary->getDescription();
    $tests['[vocabulary:node-count]'] = 1;
    $tests['[vocabulary:term-count]'] = 2;

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('vocabulary' => $this->vocabulary), array('langcode' => $language_interface->getId()));
      $this->assertEqual($output, $expected, format_string('Sanitized taxonomy vocabulary token %token replaced.', array('%token' => $input)));
    }
  }
}
