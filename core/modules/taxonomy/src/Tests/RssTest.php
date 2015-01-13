<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\RssTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\views\Views;

/**
 * Ensure that data added as terms appears in RSS feeds if "RSS Category" format
 * is selected.
 *
 * @group taxonomy
 */
class RssTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'field_ui', 'views');

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
  protected $fieldName;

  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser(['administer taxonomy', 'bypass node access', 'administer content types', 'administer node display']));
    $this->vocabulary = $this->createVocabulary();
    $this->fieldName = 'taxonomy_' . $this->vocabulary->id();

    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $this->fieldName,
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
    $field_storage->save();
    entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'article',
    ))->save();
    entity_get_form_display('node', 'article', 'default')
      ->setComponent($this->fieldName, array(
        'type' => 'options_select',
      ))
      ->save();
    entity_get_display('node', 'article', 'default')
      ->setComponent($this->fieldName, array(
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
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit[$this->fieldName . '[]'] = $term1->id();
    $this->drupalPostForm('node/add/article', $edit, t('Save'));

    // Check that the term is displayed when the RSS feed is viewed.
    $this->drupalGet('rss.xml');
    $test_element = array(
      'key' => 'category',
      'value' => $term1->getName(),
      'attributes' => array(
        'domain' => $term1->url('canonical', array('absolute' => TRUE)),
      ),
    );
    $this->assertRaw(format_xml_elements(array($test_element)), 'Term is displayed when viewing the rss feed.');

    // Test that the feed page exists for the term.
    $this->drupalGet("taxonomy/term/{$term1->id()}/feed");
    $this->assertRaw('<rss version="2.0"', "Feed page is RSS.");

    // Check that the "Exception value" is disabled by default.
    $this->drupalGet('taxonomy/term/all/feed');
    $this->assertResponse(404);
    // Set the exception value to 'all'.
    $view = Views::getView('taxonomy_term');
    $arguments = $view->getDisplay()->getOption('arguments');
    $arguments['tid']['exception']['value'] = 'all';
    $view->getDisplay()->overrideOption('arguments', $arguments);
    $view->storage->save();
    // Check the article is shown in the feed.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $raw_xml = format_xml_elements([[
      'key' => 'title',
      'value' => $node->label(),
    ]]);
    $this->drupalGet('taxonomy/term/all/feed');
    $this->assertRaw($raw_xml);
    // Unpublish the article and check that it is not shown in the feed.
    $node->setPublished(FALSE)->save();
    $this->drupalGet('taxonomy/term/all/feed');
    $this->assertNoRaw($raw_xml);
  }
}
