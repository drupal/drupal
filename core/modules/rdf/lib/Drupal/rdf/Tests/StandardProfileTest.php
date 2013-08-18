<?php

/**
 * @file
 * Contains \Drupal\rdf\Tests\StandardProfileTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\node\NodeInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests that the standard profile mappings are set and exposed as expected.
 */
class StandardProfileTest extends WebTestBase {

  /**
   * The profile used during tests.
   *
   * This purposefully uses the standard profile.
   *
   * @var string
   */
  public $profile = 'standard';

  /**
   * @var string
   */
  protected $base_uri;

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term;

  /**
   * @var \Drupal\file\FileInterface
   */
  protected $image;

  /**
   * @var \Drupal\node\NodeInterface
   */
  protected $article;

  /**
   * @var \Drupal\comment\CommentInterface
   */
  protected $articleComment;

  /**
   * @var \Drupal\node\NodeInterface
   */
  protected $page;

  /**
   * @var string
   */
  protected $imageUri;

  /**
   * @var string
   */
  protected $termUri;

  /**
   * @var string
   */
  protected $articleUri;

  /**
   * @var string
   */
  protected $pageUri;

  /**
   * @var string
   */
  protected $authorUri;

  /**
   * @var string
   */
  protected $articleCommentUri;

  /**
   * @var string
   */
  protected $commenterUri;

  public static function getInfo() {
    return array(
      'name' => 'Standard profile RDF',
      'description' => 'Tests the RDF mappings and RDFa markup of the standard profile.',
      'group' => 'RDF',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->base_uri = url('<front>', array('absolute' => TRUE));

    // Create two test users.
    $this->adminUser = $this->drupalCreateUser(array(
      'administer content types',
      'administer comments',
      'access comments',
      'access content',
    ));
    $this->webUser = $this->drupalCreateUser(array(
      'access comments',
      'post comments',
      'skip comment approval',
      'access content',
    ));

    $this->drupalLogin($this->adminUser);

    // Create term.
    $this->term = entity_create('taxonomy_term', array(
      'name' => $this->randomName(),
      'description' => $this->randomName(),
      'vid' => 'tags',
    ));
    $this->term->save();

    // Create image.
    file_unmanaged_copy(DRUPAL_ROOT . '/core/misc/druplicon.png', 'public://example.jpg');
    $this->image = entity_create('file', array('uri' => 'public://example.jpg'));
    $this->image->save();

    // Create article.
    $article_settings = array(
      'type' => 'article',
      'promote' => NODE_PROMOTED,
      'field_image' => array(
        array(
          'target_id' => $this->image->id(),
        ),
      ),
      'field_tags' => array(
        array(
          'target_id' => $this->term->id(),
        ),
      ),
    );
    $this->article = $this->drupalCreateNode($article_settings);
    // Create second article to test teaser list.
    $this->drupalCreateNode(array('type' => 'article', 'promote' => NODE_PROMOTED,));

    // Create article comment.
    $this->articleComment = $this->saveComment($this->article->id(), $this->webUser->id(), NULL, 0, 'comment_node_article');

    // Create page.
    $this->page = $this->drupalCreateNode(array('type' => 'page'));

    // Set URIs.
    // Image.
    $image_file = file_load($this->article->get('field_image')->offsetGet(0)->get('target_id')->getValue());
    $this->imageUri = entity_load('image_style', 'large')->buildUrl($image_file->getFileUri());
    // Term.
    $term_uri_info = $this->term->uri();
    $this->termUri = url($term_uri_info['path'], array('absolute' => TRUE));
    // Article.
    $article_uri_info = $this->article->uri();
    $this->articleUri = url($article_uri_info['path'], array('absolute' => TRUE));
    // Page.
    $page_uri_info = $this->page->uri();
    $this->pageUri = url($page_uri_info['path'], array('absolute' => TRUE));
    // Author.
    $this->authorUri = url('user/' . $this->adminUser->id(), array('absolute' => TRUE));
    // Comment.
    $article_comment_uri_info = $this->articleComment->uri();
    $this->articleCommentUri = url($article_comment_uri_info['path'], array('absolute' => TRUE));
    // Commenter.
    $commenter_uri_info = $this->webUser->uri();
    $this->commenterUri = url($commenter_uri_info['path'], array('absolute' => TRUE));

    $this->drupalLogout();
  }

  /**
   * Tests that data is exposed correctly when using standard profile.
   *
   * Because tests using standard profile take a very long time to run, and
   * because there is no manipulation of config or data within the test, simply
   * run all the tests from within this function.
   */
  public function testRdfaOutput() {
    $this->doFrontPageRdfaTests();
    $this->doArticleRdfaTests();
    $this->doPageRdfaTests();
    $this->doUserRdfaTests();
    $this->doTermRdfaTests();
  }

  /**
   * Tests that data is exposed in the front page teasers.
   */
  protected function doFrontPageRdfaTests() {
    // Feed the HTML into the parser.
    $path = 'node';
    $graph = $this->getRdfGraph($path);

    // Ensure that both articles are listed.
    $this->assertEqual(2, count($graph->allOfType('http://schema.org/Article')), 'Two articles found on front page.');

    // Test interaction count.
    $expected_value = array(
      'type' => 'literal',
      'value' => 'UserComments:1',
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($this->articleUri, 'http://schema.org/interactionCount', $expected_value), "Teaser comment count was found (schema:interactionCount).");

    // Test the properties that are common between pages and articles and are
    // displayed in full and teaser mode.
    $this->assertRdfaCommonNodeProperties($graph, $this->article, "Teaser");
    // Test properties that are displayed in both teaser and full mode.
    $this->assertRdfaArticleProperties($graph, "Teaser");

    // @todo Once the image points to the original instead of the processed
    //   image, move this to testArticleProperties().
    $image_file = file_load($this->article->get('field_image')->offsetGet(0)->get('target_id')->getValue());
    $image_uri = entity_load('image_style', 'medium')->buildUrl($image_file->getFileUri());
    $expected_value = array(
      'type' => 'uri',
      'value' => $image_uri,
    );
    $this->assertTrue($graph->hasProperty($this->articleUri, 'http://schema.org/image', $expected_value), "Teaser image was found (schema:image).");
  }

  /**
   * Tests that article data is exposed using RDFa.
   *
   * Two fields are not tested for output here. Changed date is not displayed
   * on the page, so there is no test for output in node view. Comment count is
   * displayed in teaser view, so it is tested in the front article tests.
   */
  protected function doArticleRdfaTests() {
    // Feed the HTML into the parser.
    $uri_info = $this->article->uri();
    $path = $uri_info['path'];
    $graph = $this->getRdfGraph($path);

    // Type.
    $this->assertEqual($graph->type($this->articleUri), 'schema:Article', 'Article type was found (schema:Article).');

    // Test the properties that are common between pages and articles.
    $this->assertRdfaCommonNodeProperties($graph, $this->article, "Article");
    // Test properties that are displayed in both teaser and full mode.
    $this->assertRdfaArticleProperties($graph, "Article");
    // Test the comment properties displayed on articles.
    $this->assertRdfaNodeCommentProperties($graph);

    // @todo Once the image points to the original instead of the processed
    //   image, move this to testArticleProperties().
    $expected_value = array(
      'type' => 'uri',
      'value' => $this->imageUri,
    );
    $this->assertTrue($graph->hasProperty($this->articleUri, 'http://schema.org/image', $expected_value), "Article image was found (schema:image).");
  }

  /**
   * Tests that page data is exposed using RDFa.
   *
   * Two fields are not tested for output here. Changed date is not displayed
   * on the page, so there is no test for output in node view. Comment count is
   * displayed in teaser view, so it is tested in the front page tests.
   */
  protected function doPageRdfaTests() {
    // The standard profile hides the created date on pages. Revert display to
    // true for testing.
    variable_set('node_submitted_page', TRUE);

    // Feed the HTML into the parser.
    $uri_info = $this->page->uri();
    $path = $uri_info['path'];
    $graph = $this->getRdfGraph($path);

    // Type.
    $this->assertEqual($graph->type($this->pageUri), 'schema:WebPage', 'Page type was found (schema:WebPage).');

    // Test the properties that are common between pages and articles.
    $this->assertRdfaCommonNodeProperties($graph, $this->page, "Page");
  }

  /**
   * Tests that user data is exposed on user page.
   */
  protected function doUserRdfaTests() {
    $this->drupalLogin($this->root_user);

    // Feed the HTML into the parser.
    $uri_info = $this->adminUser->uri();
    $path = $uri_info['path'];
    $graph = $this->getRdfGraph($path);

    // User type.
    $this->assertEqual($graph->type($this->authorUri), 'schema:Person', "User type was found (schema:Person) on user page.");

    // User name.
    $expected_value = array(
      'type' => 'literal',
      'value' => $this->adminUser->label(),
    );
    $this->assertTrue($graph->hasProperty($this->authorUri, 'http://schema.org/name', $expected_value), "User name was found (schema:name) on user page.");

    $this->drupalLogout();
  }

  /**
   * Tests that term data is exposed on term page.
   */
  protected function doTermRdfaTests() {
    // Feed the HTML into the parser.
    $uri_info = $this->term->uri();
    $path = $uri_info['path'];
    $graph = $this->getRdfGraph($path);

    // Term type.
    $this->assertEqual($graph->type($this->termUri), 'schema:Thing', "Term type was found (schema:Thing) on term page.");

    // Term name.
    $expected_value = array(
      'type' => 'literal',
      'value' => $this->term->get('name')->offsetGet(0)->get('value')->getValue(),
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($this->termUri, 'http://schema.org/name', $expected_value), "Term name was found (schema:name) on term page.");

    // @todo Add test for term description once it is a field:
    //   https://drupal.org/node/569434
  }

  /**
   * Tests output for properties held in common between articles and pages.
   *
   * @param \EasyRdf_Graph $graph
   *   The EasyRDF graph object.
   * @param \Drupal\node\NodeInterface $node
   *   The node being displayed.
   * @param string $message_prefix
   *   The word to use in the test assertion message.
   */
  protected function assertRdfaCommonNodeProperties($graph, NodeInterface $node, $message_prefix) {
    $uri_info = $node->uri();
    $uri = url($uri_info['path'], array('absolute' => TRUE));

    // Title.
    $expected_value = array(
      'type' => 'literal',
      'value' => $node->get('title')->offsetGet(0)->get('value')->getValue(),
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($uri, 'http://schema.org/name', $expected_value), "$message_prefix title was found (schema:name).");

    // Created date.
    $expected_value = array(
      'type' => 'literal',
      'value' => date_iso8601($node->get('created')->offsetGet(0)->get('value')->getValue()),
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($uri, 'http://schema.org/dateCreated', $expected_value), "$message_prefix created date was found (schema:dateCreated) in teaser.");

    // Body.
    $expected_value = array(
      'type' => 'literal',
      'value' => $node->get('body')->offsetGet(0)->get('value')->getValue(),
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($uri, 'http://schema.org/text', $expected_value), "$message_prefix body was found (schema:text) in teaser.");

    // Author.
    $expected_value = array(
      'type' => 'uri',
      'value' => $this->authorUri,
    );
    $this->assertTrue($graph->hasProperty($uri, 'http://schema.org/author', $expected_value), "$message_prefix author was found (schema:author) in teaser.");

    // Author type.
    $this->assertEqual($graph->type($this->authorUri), 'schema:Person', "$message_prefix author type was found (schema:Person).");

    // Author name.
    $expected_value = array(
      'type' => 'literal',
      'value' => $this->adminUser->label(),
    );
    $this->assertTrue($graph->hasProperty($this->authorUri, 'http://schema.org/name', $expected_value), "$message_prefix author name was found (schema:name).");
  }

  /**
   * Tests output for article properties displayed in both view modes.
   *
   * @param \EasyRdf_Graph $graph
   *   The EasyRDF graph object.
   * @param string $message_prefix
   *   The word to use in the test assertion message.
   */
  protected function assertRdfaArticleProperties($graph, $message_prefix) {
    // Tags.
    $expected_value = array(
      'type' => 'uri',
      'value' => $this->termUri,
    );
    $this->assertTrue($graph->hasProperty($this->articleUri, 'http://schema.org/about', $expected_value), "$message_prefix tag was found (schema:about).");

    // Tag type.
    $this->assertEqual($graph->type($this->termUri), 'schema:Thing', 'Tag type was found (schema:Thing).');

    // Tag name.
    $expected_value = array(
      'type' => 'literal',
      'value' => $this->term->get('name')->offsetGet(0)->get('value')->getValue(),
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($this->termUri, 'http://schema.org/name', $expected_value), "$message_prefix name was found (schema:name).");
  }

  /**
   * Tests output for comment properties on nodes in full page view mode.
   *
   * @param \EasyRdf_Graph $graph
   *   The EasyRDF graph object.
   */
  protected function assertRdfaNodeCommentProperties($graph) {
    // @todo Test relationship between comment and node once it is a field:
    //   https://drupal.org/node/731724
    // Comment type.
    $this->assertEqual($graph->type($this->articleCommentUri), 'schema:Comment', 'Comment type was found (schema:Comment).');

    // Comment title.
    $expected_value = array(
      'type' => 'literal',
      'value' => $this->articleComment->get('subject')->offsetGet(0)->get('value')->getValue(),
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($this->articleCommentUri, 'http://schema.org/name', $expected_value), 'Article comment title was found (schema:name).');

    // Comment created date.
    $expected_value = array(
      'type' => 'literal',
      'value' => date_iso8601($this->articleComment->get('created')->offsetGet(0)->get('value')->getValue()),
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($this->articleCommentUri, 'http://schema.org/dateCreated', $expected_value), 'Article comment created date was found (schema:dateCreated).');

    // Comment body.
    $text = $this->articleComment->get('comment_body')->offsetGet(0)->get('value')->getValue();
    $expected_value = array(
      'type' => 'literal',
      // There is an extra carriage return in the when parsing comments as
      // output by Bartik, so it must be added to the expected value.
      'value' => "$text
",
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($this->articleCommentUri, 'http://schema.org/text', $expected_value), 'Article comment body was found (schema:text).');

    // Comment uid.
    $expected_value = array(
      'type' => 'uri',
      'value' => $this->commenterUri,
    );
    $this->assertTrue($graph->hasProperty($this->articleCommentUri, 'http://schema.org/author', $expected_value), 'Article comment author was found (schema:author).');

    // Comment author type.
    $this->assertEqual($graph->type($this->commenterUri), 'schema:Person', 'Comment author type was found (schema:Person).');

    // Comment author name.
    $expected_value = array(
      'type' => 'literal',
      'value' => $this->webUser->get('name')->offsetGet(0)->get('value')->getValue(),
    );
    $this->assertTrue($graph->hasProperty($this->commenterUri, 'http://schema.org/name', $expected_value), 'Comment author name was found (schema:name).');
  }

  /**
   * Creates a comment entity.
   *
   * @param int $nid
   *   Node id which will hold the comment.
   * @param int $uid
   *   User id of the author of the comment. Can be NULL if $contact provided.
   * @param mixed $contact
   *   Set to NULL for no contact info, TRUE to ignore success checking, and
   *   array of values to set contact info.
   * @param int $pid
   *   Comment id of the parent comment in a thread.
   * @param string $bundle
   *   The bundle of the comment.
   *
   * @return \Drupal\comment\Entity\Comment
   *   The saved comment.
   */
  protected function saveComment($nid, $uid, $contact = NULL, $pid = 0, $bundle = '') {
    $values = array(
      'nid' => $nid,
      'uid' => $uid,
      'pid' => $pid,
      'node_type' => $bundle,
      'subject' => $this->randomName(),
      'comment_body' => $this->randomName(),
      'status' => 1,
    );
    if ($contact) {
      $values += $contact;
    }

    $comment = entity_create('comment', $values);
    $comment->save();
    return $comment;
  }

  /**
   * Get the EasyRdf_Graph object for a page.
   *
   * @param string $path
   *   The relative path to the page being tested.
   *
   * @return \EasyRdf_Graph
   *   The RDF graph object.
   */
  protected function getRdfGraph($path) {
    $parser = new \EasyRdf_Parser_Rdfa();
    $graph = new \EasyRdf_Graph();
    $parser->parse($graph, $this->drupalGet($path), 'rdfa', $this->base_uri);
    return $graph;
  }
}
