<?php

/**
 * @file
 * Definition of Drupal\rdf\Tests\TrackerAttributesTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\node\Node;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the RDF tracker page mapping.
 */
class TrackerAttributesTest extends WebTestBase {
  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'RDF tracker page mapping',
      'description' => 'Test the mapping for the tracker page and ensure the proper RDFa markup in included.',
      'group' => 'RDF',
    );
  }

  function setUp() {
    parent::setUp('rdf', 'rdf_test', 'tracker');
    // Enable anonymous posting of content.
    user_role_change_permissions(DRUPAL_ANONYMOUS_RID, array(
      'create article content' => TRUE,
      'access comments' => TRUE,
      'post comments' => TRUE,
      'skip comment approval' => TRUE,
    ));
  }

  /**
   * Create nodes as both admin and anonymous user and test for correct RDFa
   * markup on the tracker page for those nodes and their comments.
   */
  function testAttributesInTracker() {
    // Create node as anonymous user.
    $node_anon = $this->drupalCreateNode(array('type' => 'article', 'uid' => 0));
    // Create node as admin user.
    $node_admin = $this->drupalCreateNode(array('type' => 'article', 'uid' => 1));

    // Pass both the anonymously posted node and the administrator posted node
    // through to test for the RDF attributes.
    $this->_testBasicTrackerRdfaMarkup($node_anon);
    $this->_testBasicTrackerRdfaMarkup($node_admin);

  }

  /**
   * Helper function for testAttributesInTracker().
   *
   * Tests the tracker page for RDFa markup.
   *
   * @param Node $node
   * The node just created.
   */
  function _testBasicTrackerRdfaMarkup(Node $node) {
    $url = url('node/' . $node->nid);

    $user = ($node->uid == 0) ? 'Anonymous user' : 'Registered user';

    // Navigate to tracker page.
    $this->drupalGet('tracker');

    // Tests whether the about property is applied. This is implicit in the
    // success of the following tests, but making it explicit will make
    // debugging easier in case of failure.
    $tracker_about = $this->xpath('//tr[@about=:url]', array(':url' => $url));
    $this->assertTrue(!empty($tracker_about), t('About attribute found on table row for @user content.', array('@user'=> $user)));

    // Tests whether the title has the correct property attribute.
    $tracker_title = $this->xpath('//tr[@about=:url]/td[@property="dc:title" and @datatype=""]', array(':url' => $url));
    $this->assertTrue(!empty($tracker_title), t('Title property attribute found on @user content.', array('@user'=> $user)));

    // Tests whether the relationship between the content and user has been set.
    $tracker_user = $this->xpath('//tr[@about=:url]//td[contains(@rel, "sioc:has_creator")]//*[contains(@typeof, "sioc:UserAccount") and contains(@property, "foaf:name")]', array(':url' => $url));
    $this->assertTrue(!empty($tracker_user), t('Typeof and name property attributes found on @user.', array('@user'=> $user)));
    // There should be an about attribute on logged in users and no about
    // attribute for anonymous users.
    $tracker_user = $this->xpath('//tr[@about=:url]//td[@rel="sioc:has_creator"]/*[@about]', array(':url' => $url));
    if ($node->uid == 0) {
      $this->assertTrue(empty($tracker_user), t('No about attribute is present on @user.', array('@user'=> $user)));
    }
    elseif ($node->uid > 0) {
      $this->assertTrue(!empty($tracker_user), t('About attribute is present on @user.', array('@user'=> $user)));
    }

    // Tests whether the property has been set for number of comments.
    $tracker_replies = $this->xpath('//tr[@about=:url]//td[contains(@property, "sioc:num_replies") and contains(@content, "0") and @datatype="xsd:integer"]', array(':url' => $url));
    $this->assertTrue($tracker_replies, t('Num replies property and content attributes found on @user content.', array('@user'=> $user)));

    // Tests that the appropriate RDFa markup to annotate the latest activity
    // date has been added to the tracker output before comments have been
    // posted, meaning the latest activity reflects changes to the node itself.
    $isoDate = date('c', $node->changed);
    $tracker_activity = $this->xpath('//tr[@about=:url]//td[contains(@property, "dc:modified") and contains(@property, "sioc:last_activity_date") and contains(@datatype, "xsd:dateTime") and @content=:date]', array(':url' => $url, ':date' => $isoDate));
    $this->assertTrue(!empty($tracker_activity), t('Latest activity date and changed properties found when there are no comments on @user content. Latest activity date content is correct.', array('@user'=> $user)));

    // Tests that the appropriate RDFa markup to annotate the latest activity
    // date has been added to the tracker output after a comment is posted.
    $comment = array(
      'subject' => $this->randomName(),
      'comment_body[' . LANGUAGE_NOT_SPECIFIED . '][0][value]' => $this->randomName(),
    );
    $this->drupalPost('comment/reply/' . $node->nid, $comment, t('Save'));
    $this->drupalGet('tracker');

    // Tests whether the property has been set for number of comments.
    $tracker_replies = $this->xpath('//tr[@about=:url]//td[contains(@property, "sioc:num_replies") and contains(@content, "1") and @datatype="xsd:integer"]', array(':url' => $url));
    $this->assertTrue($tracker_replies, t('Num replies property and content attributes found on @user content.', array('@user'=> $user)));

    // Need to query database directly to obtain last_activity_date because
    // it cannot be accessed via node_load().
    $result = db_query('SELECT t.changed FROM {tracker_node} t WHERE t.nid = (:nid)', array(':nid' => $node->nid));
    foreach ($result as $node) {
      $expected_last_activity_date = $node->changed;
    }
    $isoDate = date('c', $expected_last_activity_date);
    $tracker_activity = $this->xpath('//tr[@about=:url]//td[@property="sioc:last_activity_date" and @datatype="xsd:dateTime" and @content=:date]', array(':url' => $url, ':date' => $isoDate));
    $this->assertTrue(!empty($tracker_activity), t('Latest activity date found when there are comments on @user content. Latest activity date content is correct.', array('@user'=> $user)));
  }
}
