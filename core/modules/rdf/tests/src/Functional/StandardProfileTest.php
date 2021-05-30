<?php

namespace Drupal\Tests\rdf\Functional;

use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\comment\Entity\Comment;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\rdf\Traits\RdfParsingTrait;

/**
 * Tests the RDF mappings and RDFa markup of the standard profile.
 *
 * @group rdf
 */
class StandardProfileTest extends BrowserTestBase {

  use RdfParsingTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

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
  protected $baseUri;

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

  protected function setUp(): void {
    parent::setUp();

    $this->baseUri = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();

    // Create two test users.
    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'administer comments',
      'access comments',
      'access content',
    ]);
    $this->webUser = $this->drupalCreateUser([
      'access comments',
      'post comments',
      'skip comment approval',
      'access content',
    ]);

    $this->drupalLogin($this->adminUser);

    // Create term.
    $this->term = Term::create([
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => 'tags',
    ]);
    $this->term->save();

    // Create image.
    \Drupal::service('file_system')->copy($this->root . '/core/misc/druplicon.png', 'public://example.jpg');
    $this->image = File::create(['uri' => 'public://example.jpg']);
    $this->image->save();

    // Create article.
    $article_settings = [
      'type' => 'article',
      'promote' => NodeInterface::PROMOTED,
      'field_image' => [
        [
          'target_id' => $this->image->id(),
        ],
      ],
      'field_tags' => [
        [
          'target_id' => $this->term->id(),
        ],
      ],
    ];
    $this->article = $this->drupalCreateNode($article_settings);
    // Create second article to test teaser list.
    $this->drupalCreateNode(['type' => 'article', 'promote' => NodeInterface::PROMOTED]);

    // Create article comment.
    $this->articleComment = $this->saveComment($this->article->id(), $this->webUser->id(), NULL, 0);

    // Create page.
    $this->page = $this->drupalCreateNode(['type' => 'page']);

    // Set URIs.
    // Image.
    $image_file = $this->article->get('field_image')->entity;
    $this->imageUri = ImageStyle::load('large')->buildUrl($image_file->getFileUri());
    // Term.
    $this->termUri = $this->term->toUrl('canonical', ['absolute' => TRUE])->toString();
    // Article.
    $this->articleUri = $this->article->toUrl('canonical', ['absolute' => TRUE])->toString();
    // Page.
    $this->pageUri = $this->page->toUrl('canonical', ['absolute' => TRUE])->toString();
    // Author.
    $this->authorUri = $this->adminUser->toUrl('canonical', ['absolute' => TRUE])->toString();
    // Comment.
    $this->articleCommentUri = $this->articleComment->toUrl('canonical', ['absolute' => TRUE])->toString();
    // Commenter.
    $this->commenterUri = $this->webUser->toUrl('canonical', ['absolute' => TRUE])->toString();

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
    $this->drupalGet(Url::fromRoute('<front>'));

    // Ensure that both articles are listed.
    // $this->assertCount(2, $this->getRdfGraph(Url::fromRoute('<front>'), $this->baseUri)->allOfType('http://schema.org/Article'), 'Two articles found on front page.');
    $this->assertEquals(2, $this->getElementByRdfTypeCount(Url::fromRoute('<front>'), $this->baseUri, 'http://schema.org/Article'), 'Two articles found on front page.');

    // Test interaction count.
    $expected_value = [
      'type' => 'literal',
      'value' => 'UserComments:1',
      'lang' => 'en',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $this->articleUri, 'http://schema.org/interactionCount', $expected_value), 'Teaser comment count was found (schema:interactionCount).');

    // Test the properties that are common between pages and articles and are
    // displayed in full and teaser mode.
    $this->assertRdfaCommonNodeProperties($this->article, "Teaser");
    // Test properties that are displayed in both teaser and full mode.
    $this->assertRdfaArticleProperties("Teaser");

    // @todo Once the image points to the original instead of the processed
    //   image, move this to testArticleProperties().
    $expected_value = [
      'type' => 'uri',
      'value' => $this->imageUri,
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $this->articleUri, 'http://schema.org/image', $expected_value), 'Teaser image was found (schema:image).');
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
    $this->drupalGet($this->article->toUrl());

    // Type.
    $this->assertEquals('schema:Article', $this->getElementRdfType($this->article->toUrl(), $this->baseUri, $this->articleUri), 'Article type was found (schema:Article).');

    // Test the properties that are common between pages and articles.
    $this->assertRdfaCommonNodeProperties($this->article, "Article");
    // Test properties that are displayed in both teaser and full mode.
    $this->assertRdfaArticleProperties("Article");
    // Test the comment properties displayed on articles.
    $this->assertRdfaNodeCommentProperties();

    // @todo Once the image points to the original instead of the processed
    //   image, move this to testArticleProperties().

    $expected_value = [
      'type' => 'uri',
      'value' => $this->imageUri,
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $this->articleUri, 'http://schema.org/image', $expected_value), 'Teaser image was found (schema:image).');
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
    // @todo Clean-up standard profile defaults.
    $node_type = NodeType::load('page');
    $node_type->setDisplaySubmitted(TRUE);
    $node_type->save();

    // Type.
    $this->assertEquals('schema:WebPage', $this->getElementRdfType($this->page->toUrl(), $this->baseUri, $this->pageUri), 'Page type was found (schema:WebPage).');

    // Test the properties that are common between pages and articles.
    $this->assertRdfaCommonNodeProperties($this->page, "Page");
  }

  /**
   * Tests that user data is exposed on user page.
   */
  protected function doUserRdfaTests() {
    $this->drupalLogin($this->rootUser);

    // User type.
    $this->assertEquals('schema:Person', $this->getElementRdfType($this->adminUser->toUrl(), $this->baseUri, $this->authorUri), 'User type was found (schema:Person) on user page.');

    // User name.
    $expected_value = [
      'type' => 'literal',
      'value' => $this->adminUser->label(),
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $this->authorUri, 'http://schema.org/name', $expected_value), 'User name was found (schema:name) on user page.');

    $this->drupalLogout();
  }

  /**
   * Tests that term data is exposed on term page.
   */
  protected function doTermRdfaTests() {

    // Term type.
    $this->assertEquals('schema:Thing', $this->getElementRdfType($this->term->toUrl(), $this->baseUri, $this->termUri), 'Term type was found (schema:Thing) on term page.');

    // Term name.
    $expected_value = [
      'type' => 'literal',
      'value' => $this->term->getName(),
      'lang' => 'en',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $this->termUri, 'http://schema.org/name', $expected_value), 'Term name was found (schema:name) on term page.');
    // @todo Add test for term description once it is a field:
    //   https://www.drupal.org/node/569434.
  }

  /**
   * Tests output for properties held in common between articles and pages.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node being displayed.
   * @param string $message_prefix
   *   The word to use in the test assertion message.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function assertRdfaCommonNodeProperties(NodeInterface $node, $message_prefix) {
    $this->drupalGet($node->toUrl());
    $uri = $node->toUrl('canonical', ['absolute' => TRUE])->toString();

    // Title.
    $expected_value = [
      'type' => 'literal',
      'value' => $node->get('title')->value,
      'lang' => 'en',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $uri, 'http://schema.org/name', $expected_value), "$message_prefix title was found (schema:name).");

    // Created date.
    $expected_value = [
      'type' => 'literal',
      'value' => $this->container->get('date.formatter')->format($node->get('created')->value, 'custom', 'c', 'UTC'),
      'lang' => 'en',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $uri, 'http://schema.org/dateCreated', $expected_value), "$message_prefix created date was found (schema:dateCreated) in teaser.");

    // Body.
    $expected_value = [
      'type' => 'literal',
      'value' => $node->get('body')->value,
      'lang' => 'en',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $uri, 'http://schema.org/text', $expected_value), "$message_prefix body was found (schema:text) in teaser.");

    // Author.
    $expected_value = [
      'type' => 'uri',
      'value' => $this->authorUri,
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $uri, 'http://schema.org/author', $expected_value), "$message_prefix author was found (schema:author) in teaser.");

    // Author type.
    $this->assertEquals('schema:Person', $this->getElementRdfType($node->toUrl(), $this->baseUri, $this->authorUri), '$message_prefix author type was found (schema:Person).');

    // Author name.
    $expected_value = [
      'type' => 'literal',
      'value' => $this->adminUser->label(),
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $this->authorUri, 'http://schema.org/name', $expected_value), "$message_prefix author name was found (schema:name).");
  }

  /**
   * Tests output for article properties displayed in both view modes.
   *
   * @param string $message_prefix
   *   The word to use in the test assertion message.
   */
  protected function assertRdfaArticleProperties($message_prefix) {
    // Tags.
    $expected_value = [
      'type' => 'uri',
      'value' => $this->termUri,
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $this->articleUri, 'http://schema.org/about', $expected_value), "$message_prefix tag was found (schema:about).");

    // Tag type.
    // @todo Enable with https://www.drupal.org/node/2072791.
    // $this->assertEquals('schema:Thing', $graph->type($this->termUri), 'Tag type was found (schema:Thing).');

    // Tag name.
    $expected_value = [
      'type' => 'literal',
      'value' => $this->term->getName(),
      'lang' => 'en',
    ];
    // @todo Enable with https://www.drupal.org/node/2072791.
    // $this->assertTrue($graph->hasProperty($this->termUri, 'http://schema.org/name', $expected_value), "$message_prefix name was found (schema:name).");
  }

  /**
   * Tests output for comment properties on nodes in full page view mode.
   */
  protected function assertRdfaNodeCommentProperties() {

    $this->drupalGet($this->article->toUrl());
    // Relationship between node and comment.
    $expected_value = [
      'type' => 'uri',
      'value' => $this->articleCommentUri,
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $this->articleUri, 'http://schema.org/comment', $expected_value), "Relationship between node and comment found (schema:comment).");

    // Comment type.
    $this->assertEquals('schema:Comment', $this->getElementRdfType($this->article->toUrl(), $this->baseUri, $this->articleCommentUri), 'Comment type was found (schema:Comment).');

    // Comment title.
    $expected_value = [
      'type' => 'literal',
      'value' => $this->articleComment->get('subject')->value,
      'lang' => 'en',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $this->articleCommentUri, 'http://schema.org/name', $expected_value), "Article comment title was found (schema:name).");

    // Comment created date.
    $expected_value = [
      'type' => 'literal',
      'value' => $this->container->get('date.formatter')->format($this->articleComment->get('created')->value, 'custom', 'c', 'UTC'),
      'lang' => 'en',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $this->articleCommentUri, 'http://schema.org/dateCreated', $expected_value), "Article comment created date was found (schema:dateCreated).");

    // Comment body.
    $text = $this->articleComment->get('comment_body')->value;
    $expected_value = [
      'type' => 'literal',
      // There is an extra carriage return in the when parsing comments as
      // output by Bartik, so it must be added to the expected value.
      'value' => "$text
",
      'lang' => 'en',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $this->articleCommentUri, 'http://schema.org/text', $expected_value), "Article comment body was found (schema:text).");

    // Comment uid.
    $expected_value = [
      'type' => 'uri',
      'value' => $this->commenterUri,
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $this->articleCommentUri, 'http://schema.org/author', $expected_value), "Article comment author was found (schema:author).");

    // Comment author type.
    $this->assertEquals('schema:Person', $this->getElementRdfType($this->article->toUrl(), $this->baseUri, $this->commenterUri), 'Comment author type was found (schema:Person).');

    // Comment author name.
    $expected_value = [
      'type' => 'literal',
      'value' => $this->webUser->getAccountName(),
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $this->commenterUri, 'http://schema.org/name', $expected_value), "Comment author name was found (schema:name).");
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
   *
   * @return \Drupal\comment\Entity\Comment
   *   The saved comment.
   */
  protected function saveComment($nid, $uid, $contact = NULL, $pid = 0) {
    $values = [
      'entity_id' => $nid,
      'entity_type' => 'node',
      'field_name' => 'comment',
      'uid' => $uid,
      'pid' => $pid,
      'subject' => $this->randomMachineName(),
      'comment_body' => $this->randomMachineName(),
      'status' => 1,
    ];
    if ($contact) {
      $values += $contact;
    }

    $comment = Comment::create($values);
    $comment->save();
    return $comment;
  }

}
