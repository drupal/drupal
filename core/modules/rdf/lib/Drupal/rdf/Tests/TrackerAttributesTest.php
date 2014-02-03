<?php

/**
 * @file
 * Contains \Drupal\rdf\Tests\TrackerAttributesTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the RDF tracker page mapping.
 */
class TrackerAttributesTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rdf', 'tracker');

  public static function getInfo() {
    return array(
      'name' => 'RDFa markup for tracker page',
      'description' => 'Test the mapping for the tracker page and ensure the proper RDFa markup in included.',
      'group' => 'RDF',
    );
  }

  function setUp() {
    parent::setUp();

    // Creates article content type.
    $this->drupalCreateContentType(array('type' => 'article', 'name' => t('Article')));

    // Set bundle RDF mapping config for article.
    $mapping = rdf_get_mapping('node', 'article');
    // Set fields RDF mapping config for article.
    $node_shared_field_mappings = array(
      'title' => array(
        'properties' => array('dc:title'),
      ),
      'created' => array(
        'properties' => array('dc:date', 'dc:created'),
        'datatype' => 'xsd:dateTime',
        'datatype_callback' => array('callable' => 'date_iso8601'),
      ),
      'changed' => array(
        'properties' => array('dc:modified'),
        'datatype' => 'xsd:dateTime',
        'datatype_callback' => array('callable' => 'date_iso8601'),
      ),
      'body' => array(
        'properties' => array('content:encoded'),
      ),
      'uid' => array(
        'properties' => array('sioc:has_creator'),
        'mapping_type' => 'rel',
      ),
      'name' => array(
        'properties' => array('foaf:name'),
      ),
      'comment_count' => array(
        'properties' => array('sioc:num_replies'),
        'datatype' => 'xsd:integer',
      ),
      'last_activity' => array(
        'properties' => array('sioc:last_activity_date'),
        'datatype' => 'xsd:dateTime',
        'datatype_callback' => array('callable' => 'date_iso8601'),
      ),
    );
    // Iterate over field mappings and save.
    foreach ($node_shared_field_mappings as $field_name => $field_mapping) {
      $mapping->setFieldMapping($field_name, $field_mapping)->save();
    }

    // Enables anonymous posting of content.
    user_role_change_permissions(DRUPAL_ANONYMOUS_RID, array(
      'create article content' => TRUE,
      'access comments' => TRUE,
      'post comments' => TRUE,
      'skip comment approval' => TRUE,
    ));

    // Create comment field on article.
    $this->container->get('comment.manager')->addDefaultField('node', 'article');

    // Sets base URI of the site used by the RDFa parser.
    $this->base_uri = url('<front>', array('absolute' => TRUE));
  }

  /**
   * Tests for correct attributes on tracker page.
   *
   * Creates nodes as both admin and anonymous user and tests for correct RDFa
   * markup on the tracker page for those nodes and their comments.
   */
  function testAttributesInTracker() {
    // Creates node as anonymous user.
    $node_anon = $this->drupalCreateNode(array('type' => 'article', 'uid' => 0));
    // Creates node as admin user.
    $node_admin = $this->drupalCreateNode(array('type' => 'article', 'uid' => 1));

    // Passes both the anonymously posted node and the administrator posted node
    // through to test for the RDF attributes.
    $this->_testBasicTrackerRdfaMarkup($node_anon);
    $this->_testBasicTrackerRdfaMarkup($node_admin);
  }

  /**
   * Helper function for testAttributesInTracker().
   *
   * Tests the tracker page for RDFa markup.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   * The node just created.
   */
  function _testBasicTrackerRdfaMarkup(NodeInterface $node) {
    $node_uri = url('node/' . $node->id(), array('absolute' => TRUE));
    $user_uri = url('user/' . $node->getOwnerId(), array('absolute' => TRUE));

    // Parses tracker page where the nodes are displayed in a table.
    $parser = new \EasyRdf_Parser_Rdfa();
    $graph = new \EasyRdf_Graph();
    $parser->parse($graph, $this->drupalGet('tracker'), 'rdfa', $this->base_uri);

    // Inspects RDF graph output.
    // Node title.
    $expected_value = array(
      'type' => 'literal',
      // The theme layer adds a space after the title a element, and the RDFa
      // attribute is on the wrapping td. Adds a space to match this.
      'value' => $node->getTitle() . ' ',
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($node_uri, 'http://purl.org/dc/terms/title', $expected_value), 'Title found in RDF output (dc:title).');
    // Number of comments.
    $expected_value = array(
      'type' => 'literal',
      'value' => '0',
      'datatype' => 'http://www.w3.org/2001/XMLSchema#integer',
    );
    $this->assertTrue($graph->hasProperty($node_uri, 'http://rdfs.org/sioc/ns#num_replies', $expected_value), 'Number of comments found in RDF output (sioc:num_replies).');
    // Node relation to author.
    $expected_value = array(
      'type' => 'uri',
      'value' => $user_uri,
    );
    if ($node->getOwnerId() == 0) {
      $this->assertFalse($graph->hasProperty($node_uri, 'http://rdfs.org/sioc/ns#has_creator', $expected_value), 'No relation to author found in RDF output (sioc:has_creator).');
    }
    elseif ($node->getOwnerId() > 0) {
      $this->assertTrue($graph->hasProperty($node_uri, 'http://rdfs.org/sioc/ns#has_creator', $expected_value), 'Relation to author found in RDF output (sioc:has_creator).');
    }
    // Last updated.
    $expected_value = array(
      'type' => 'literal',
      'value' => date('c', $node->getChangedTime()),
      'datatype' => 'http://www.w3.org/2001/XMLSchema#dateTime',
    );
    $this->assertTrue($graph->hasProperty($node_uri, 'http://rdfs.org/sioc/ns#last_activity_date', $expected_value), 'Last activity date found in RDF output (sioc:last_activity_date).');


    // Adds new comment to ensure the tracker is updated accordingly.
    $comment = array(
      'subject' => $this->randomName(),
      'comment_body[0][value]' => $this->randomName(),
    );
    $this->drupalPostForm('comment/reply/node/' . $node->id() .'/comment', $comment, t('Save'));

    // Parses tracker page where the nodes are displayed in a table.
    $parser = new \EasyRdf_Parser_Rdfa();
    $graph = new \EasyRdf_Graph();
    $parser->parse($graph, $this->drupalGet('tracker'), 'rdfa', $this->base_uri);

    // Number of comments.
    $expected_value = array(
      'type' => 'literal',
      'value' => '1',
      'datatype' => 'http://www.w3.org/2001/XMLSchema#integer',
    );
    $this->assertTrue($graph->hasProperty($node_uri, 'http://rdfs.org/sioc/ns#num_replies', $expected_value), 'Number of comments found in RDF output (sioc:num_replies).');
    // Last updated due to new comment.
    // last_activity_date needs to be queried from the database directly because
    // it cannot be accessed via node_load().
    $expected_last_activity_date = db_query('SELECT t.changed FROM {tracker_node} t WHERE t.nid = (:nid)', array(':nid' => $node->id()))->fetchField();
    $expected_value = array(
      'type' => 'literal',
      'value' => date('c', $expected_last_activity_date),
      'datatype' => 'http://www.w3.org/2001/XMLSchema#dateTime',
    );
    $this->assertTrue($graph->hasProperty($node_uri, 'http://rdfs.org/sioc/ns#last_activity_date', $expected_value), 'Last activity date after new comment has been posted found in RDF output (sioc:last_activity_date).');
  }
}
