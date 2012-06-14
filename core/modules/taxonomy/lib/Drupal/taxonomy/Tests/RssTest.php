<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\RssTest.
 */

namespace Drupal\taxonomy\Tests;

/**
 * Tests the rendering of term reference fields in RSS feeds.
 */
class RssTest extends TaxonomyTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy RSS Content.',
      'description' => 'Ensure that data added as terms appears in RSS feeds if "RSS Category" format is selected.',
      'group' => 'Taxonomy',
    );
  }

  function setUp() {
    parent::setUp(array('node', 'field_ui'));
    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy', 'bypass node access', 'administer content types'));
    $this->drupalLogin($this->admin_user);
    $this->vocabulary = $this->createVocabulary();

    $field = array(
      'field_name' => 'taxonomy_' . $this->vocabulary->machine_name,
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
    field_create_field($field);

    $this->instance = array(
      'field_name' => 'taxonomy_' . $this->vocabulary->machine_name,
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
    field_create_instance($this->instance);
  }

  /**
   * Tests that terms added to nodes are displayed in core RSS feed.
   *
   * Create a node and assert that taxonomy terms appear in rss.xml.
   */
  function testTaxonomyRss() {
    // Create two taxonomy terms.
    $term1 = $this->createTerm($this->vocabulary);

    // RSS display must be added manually.
    $this->drupalGet("admin/structure/types/manage/article/display");
    $edit = array(
      "view_modes_custom[rss]" => '1',
    );
    $this->drupalPost(NULL, $edit, t('Save'));

    // Change the format to 'RSS category'.
    $this->drupalGet("admin/structure/types/manage/article/display/rss");
    $edit = array(
      "fields[taxonomy_" . $this->vocabulary->machine_name . "][type]" => 'taxonomy_term_reference_rss_category',
    );
    $this->drupalPost(NULL, $edit, t('Save'));

    // Post an article.
    $edit = array();
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $edit["title"] = $this->randomName();
    $edit[$this->instance['field_name'] . '[' . $langcode . '][]'] = $term1->tid;
    $this->drupalPost('node/add/article', $edit, t('Save'));

    // Check that the term is displayed when the RSS feed is viewed.
    $this->drupalGet('rss.xml');
    $test_element = array(
      'key' => 'category',
      'value' => $term1->name,
      'attributes' => array(
        'domain' => url('taxonomy/term/' . $term1->tid, array('absolute' => TRUE)),
      ),
    );
    $this->assertRaw(format_xml_elements(array($test_element)), 'Term is displayed when viewing the rss feed.');
  }
}
