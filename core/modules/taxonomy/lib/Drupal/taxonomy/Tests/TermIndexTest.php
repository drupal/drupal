<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\TermIndexTest.
 */

namespace Drupal\taxonomy\Tests;

/**
 * Tests the hook implementations that maintain the taxonomy index.
 */
class TermIndexTest extends TaxonomyTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy term index',
      'description' => 'Tests the hook implementations that maintain the taxonomy index.',
      'group' => 'Taxonomy',
    );
  }

  function setUp() {
    parent::setUp();

    // Create an administrative user.
    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy', 'bypass node access'));
    $this->drupalLogin($this->admin_user);

    // Create a vocabulary and add two term reference fields to article nodes.
    $this->vocabulary = $this->createVocabulary();

    $this->field_name_1 = drupal_strtolower($this->randomName());
    $this->field_1 = array(
      'field_name' => $this->field_name_1,
      'type' => 'taxonomy_term_reference',
      'cardinality' => FIELD_CARDINALITY_UNLIMITED,
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $this->vocabulary->machine_name,
            'parent' => 0,
          ),
        ),
      ),
    );
    field_create_field($this->field_1);
    $this->instance_1 = array(
      'field_name' => $this->field_name_1,
      'bundle' => 'article',
      'entity_type' => 'node',
      'widget' => array(
        'type' => 'options_select',
      ),
      'display' => array(
        'default' => array(
          'type' => 'taxonomy_term_reference_link',
        ),
      ),
    );
    field_create_instance($this->instance_1);

    $this->field_name_2 = drupal_strtolower($this->randomName());
    $this->field_2 = array(
      'field_name' => $this->field_name_2,
      'type' => 'taxonomy_term_reference',
      'cardinality' => FIELD_CARDINALITY_UNLIMITED,
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $this->vocabulary->machine_name,
            'parent' => 0,
          ),
        ),
      ),
    );
    field_create_field($this->field_2);
    $this->instance_2 = array(
      'field_name' => $this->field_name_2,
      'bundle' => 'article',
      'entity_type' => 'node',
      'widget' => array(
        'type' => 'options_select',
      ),
      'display' => array(
        'default' => array(
          'type' => 'taxonomy_term_reference_link',
        ),
      ),
    );
    field_create_instance($this->instance_2);
  }

  /**
   * Tests that the taxonomy index is maintained properly.
   */
  function testTaxonomyIndex() {
    // Create terms in the vocabulary.
    $term_1 = $this->createTerm($this->vocabulary);
    $term_2 = $this->createTerm($this->vocabulary);

    // Post an article.
    $edit = array();
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $edit["title"] = $this->randomName();
    $edit["body[$langcode][0][value]"] = $this->randomName();
    $edit["{$this->field_name_1}[$langcode][]"] = $term_1->tid;
    $edit["{$this->field_name_2}[$langcode][]"] = $term_1->tid;
    $this->drupalPost('node/add/article', $edit, t('Save'));

    // Check that the term is indexed, and only once.
    $node = $this->drupalGetNodeByTitle($edit["title"]);
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->nid,
      ':tid' => $term_1->tid,
    ))->fetchField();
    $this->assertEqual(1, $index_count, 'Term 1 is indexed once.');

    // Update the article to change one term.
    $edit["{$this->field_name_1}[$langcode][]"] = $term_2->tid;
    $this->drupalPost('node/' . $node->nid . '/edit', $edit, t('Save'));

    // Check that both terms are indexed.
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->nid,
      ':tid' => $term_1->tid,
    ))->fetchField();
    $this->assertEqual(1, $index_count, 'Term 1 is indexed.');
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->nid,
      ':tid' => $term_2->tid,
    ))->fetchField();
    $this->assertEqual(1, $index_count, 'Term 2 is indexed.');

    // Update the article to change another term.
    $edit["{$this->field_name_2}[$langcode][]"] = $term_2->tid;
    $this->drupalPost('node/' . $node->nid . '/edit', $edit, t('Save'));

    // Check that only one term is indexed.
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->nid,
      ':tid' => $term_1->tid,
    ))->fetchField();
    $this->assertEqual(0, $index_count, 'Term 1 is not indexed.');
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->nid,
      ':tid' => $term_2->tid,
    ))->fetchField();
    $this->assertEqual(1, $index_count, 'Term 2 is indexed once.');

    // Redo the above tests without interface.
    $node->title = $this->randomName();
    unset($node->{$this->field_name_1});
    unset($node->{$this->field_name_2});

    // Update the article with no term changed.
    $node->save();

    // Check that the index was not changed.
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->nid,
      ':tid' => $term_1->tid,
    ))->fetchField();
    $this->assertEqual(0, $index_count, 'Term 1 is not indexed.');
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->nid,
      ':tid' => $term_2->tid,
    ))->fetchField();
    $this->assertEqual(1, $index_count, 'Term 2 is indexed once.');

    // Update the article to change one term.
    $node->{$this->field_name_1}[$langcode] = array(array('tid' => $term_1->tid));
    $node->save();

    // Check that both terms are indexed.
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->nid,
      ':tid' => $term_1->tid,
    ))->fetchField();
    $this->assertEqual(1, $index_count, 'Term 1 is indexed.');
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->nid,
      ':tid' => $term_2->tid,
    ))->fetchField();
    $this->assertEqual(1, $index_count, 'Term 2 is indexed.');

    // Update the article to change another term.
    $node->{$this->field_name_2}[$langcode] = array(array('tid' => $term_1->tid));
    $node->save();

    // Check that only one term is indexed.
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->nid,
      ':tid' => $term_1->tid,
    ))->fetchField();
    $this->assertEqual(1, $index_count, 'Term 1 is indexed once.');
    $index_count = db_query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', array(
      ':nid' => $node->nid,
      ':tid' => $term_2->tid,
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
    $term1->parent = array($term2->tid);
    taxonomy_term_save($term1);

    // Verify that the page breadcrumbs include a link to the parent term.
    $this->drupalGet('taxonomy/term/' . $term1->tid);
    $this->assertRaw(l($term2->name, 'taxonomy/term/' . $term2->tid), 'Parent term link is displayed when viewing the node.');
  }
}
