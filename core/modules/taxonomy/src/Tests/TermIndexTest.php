<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\TermIndexTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Tests the hook implementations that maintain the taxonomy index.
 *
 * @group taxonomy
 */
class TermIndexTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views');

  /**
   * Vocabulary for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Name of the taxonomy term reference field.
   *
   * @var string
   */
  protected $fieldName1;

  /**
   * Name of the taxonomy term reference field.
   *
   * @var string
   */
  protected $fieldName2;

  protected function setUp() {
    parent::setUp();

    // Create an administrative user.
    $this->drupalLogin($this->drupalCreateUser(['administer taxonomy', 'bypass node access']));

    // Create a vocabulary and add two term reference fields to article nodes.
    $this->vocabulary = $this->createVocabulary();

    $this->fieldName1 = Unicode::strtolower($this->randomMachineName());
    entity_create('field_storage_config', array(
      'field_name' => $this->fieldName1,
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
      'field_name' => $this->fieldName1,
      'bundle' => 'article',
      'entity_type' => 'node',
    ))->save();
    entity_get_form_display('node', 'article', 'default')
      ->setComponent($this->fieldName1, array(
        'type' => 'options_select',
      ))
      ->save();
    entity_get_display('node', 'article', 'default')
      ->setComponent($this->fieldName1, array(
        'type' => 'taxonomy_term_reference_link',
      ))
      ->save();

    $this->fieldName2 = Unicode::strtolower($this->randomMachineName());
    entity_create('field_storage_config', array(
      'field_name' => $this->fieldName2,
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
      'field_name' => $this->fieldName2,
      'bundle' => 'article',
      'entity_type' => 'node',
    ))->save();
    entity_get_form_display('node', 'article', 'default')
      ->setComponent($this->fieldName2, array(
        'type' => 'options_select',
      ))
      ->save();
    entity_get_display('node', 'article', 'default')
      ->setComponent($this->fieldName2, array(
        'type' => 'taxonomy_term_reference_link',
      ))
      ->save();
  }

  /**
   * Tests that the taxonomy index is maintained properly.
   */
  function testTaxonomyIndex() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    // Create terms in the vocabulary.
    $term_1 = $this->createTerm($this->vocabulary);
    $term_2 = $this->createTerm($this->vocabulary);

    // Post an article.
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit['body[0][value]'] = $this->randomMachineName();
    $edit["{$this->fieldName1}[]"] = $term_1->id();
    $edit["{$this->fieldName2}[]"] = $term_1->id();
    $this->drupalPostForm('node/add/article', $edit, t('Save'));

    // Check that the term is indexed, and only once.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->id(),
      ':tid' => $term_1->id(),
    ))->fetchField();
    $this->assertEqual(1, $index_count, 'Term 1 is indexed once.');

    // Update the article to change one term.
    $edit["{$this->fieldName1}[]"] = $term_2->id();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    // Check that both terms are indexed.
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->id(),
      ':tid' => $term_1->id(),
    ))->fetchField();
    $this->assertEqual(1, $index_count, 'Term 1 is indexed.');
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->id(),
      ':tid' => $term_2->id(),
    ))->fetchField();
    $this->assertEqual(1, $index_count, 'Term 2 is indexed.');

    // Update the article to change another term.
    $edit["{$this->fieldName2}[]"] = $term_2->id();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    // Check that only one term is indexed.
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->id(),
      ':tid' => $term_1->id(),
    ))->fetchField();
    $this->assertEqual(0, $index_count, 'Term 1 is not indexed.');
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->id(),
      ':tid' => $term_2->id(),
    ))->fetchField();
    $this->assertEqual(1, $index_count, 'Term 2 is indexed once.');

    // Redo the above tests without interface.
    $node_storage->resetCache(array($node->id()));
    $node = $node_storage->load($node->id());
    $node->title = $this->randomMachineName();

    // Update the article with no term changed.
    $node->save();

    // Check that the index was not changed.
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->id(),
      ':tid' => $term_1->id(),
    ))->fetchField();
    $this->assertEqual(0, $index_count, 'Term 1 is not indexed.');
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->id(),
      ':tid' => $term_2->id(),
    ))->fetchField();
    $this->assertEqual(1, $index_count, 'Term 2 is indexed once.');

    // Update the article to change one term.
    $node->{$this->fieldName1} = array(array('target_id' => $term_1->id()));
    $node->save();

    // Check that both terms are indexed.
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->id(),
      ':tid' => $term_1->id(),
    ))->fetchField();
    $this->assertEqual(1, $index_count, 'Term 1 is indexed.');
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->id(),
      ':tid' => $term_2->id(),
    ))->fetchField();
    $this->assertEqual(1, $index_count, 'Term 2 is indexed.');

    // Update the article to change another term.
    $node->{$this->fieldName2} = array(array('target_id' => $term_1->id()));
    $node->save();

    // Check that only one term is indexed.
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->id(),
      ':tid' => $term_1->id(),
    ))->fetchField();
    $this->assertEqual(1, $index_count, 'Term 1 is indexed once.');
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->id(),
      ':tid' => $term_2->id(),
    ))->fetchField();
    $this->assertEqual(0, $index_count, 'Term 2 is not indexed.');
  }

  /**
   * Tests that there is a link to the parent term on the child term page.
   */
  function testTaxonomyTermHierarchyBreadcrumbs() {
    // Create two taxonomy terms and set term2 as the parent of term1.
    $term1 = $this->createTerm($this->vocabulary);
    $term2 = $this->createTerm($this->vocabulary);
    $term1->parent = array($term2->id());
    $term1->save();

    // Verify that the page breadcrumbs include a link to the parent term.
    $this->drupalGet('taxonomy/term/' . $term1->id());
    $this->assertRaw(\Drupal::l($term2->getName(), $term2->urlInfo()), 'Parent term link is displayed when viewing the node.');
  }
}
