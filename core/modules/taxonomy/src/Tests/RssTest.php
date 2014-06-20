<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\RssTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Tests the rendering of term reference fields in RSS feeds.
 */
class RssTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'field_ui', 'views');

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy RSS Content.',
      'description' => 'Ensure that data added as terms appears in RSS feeds if "RSS Category" format is selected.',
      'group' => 'Taxonomy',
    );
  }

  function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy', 'bypass node access', 'administer content types', 'administer node display'));
    $this->drupalLogin($this->admin_user);
    $this->vocabulary = $this->createVocabulary();
    $this->field_name = 'taxonomy_' . $this->vocabulary->id();

    $this->field = entity_create('field_config', array(
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
    ));
    $this->field->save();
    entity_create('field_instance_config', array(
      'field' => $this->field,
      'bundle' => 'article',
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
      "display_modes_custom[rss]" => '1',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Change the format to 'RSS category'.
    $this->drupalGet("admin/structure/types/manage/article/display/rss");
    $edit = array(
      "fields[taxonomy_" . $this->vocabulary->id() . "][type]" => 'taxonomy_term_reference_rss_category',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Post an article.
    $edit = array();
    $edit['title[0][value]'] = $this->randomName();
    $edit[$this->field_name . '[]'] = $term1->id();
    $this->drupalPostForm('node/add/article', $edit, t('Save'));

    // Check that the term is displayed when the RSS feed is viewed.
    $this->drupalGet('rss.xml');
    $test_element = array(
      'key' => 'category',
      'value' => $term1->getName(),
      'attributes' => array(
        'domain' => url('taxonomy/term/' . $term1->id(), array('absolute' => TRUE)),
      ),
    );
    $this->assertRaw(format_xml_elements(array($test_element)), 'Term is displayed when viewing the rss feed.');

    // Test that the feed page exists for the term.
    $this->drupalGet("taxonomy/term/{$term1->id()}/feed");
    $this->assertRaw('<rss version="2.0"', "Feed page is RSS.");
  }
}
