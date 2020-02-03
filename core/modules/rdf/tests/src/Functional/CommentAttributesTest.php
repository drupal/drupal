<?php

namespace Drupal\Tests\rdf\Functional;

use Drupal\Core\Url;
use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\Tests\comment\Functional\CommentTestBase;
use Drupal\Tests\rdf\Traits\RdfParsingTrait;
use Drupal\user\RoleInterface;
use Drupal\comment\Entity\Comment;

/**
 * Tests the RDFa markup of comments.
 *
 * @group rdf
 */
class CommentAttributesTest extends CommentTestBase {

  use RdfParsingTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['views', 'node', 'comment', 'rdf'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * URI of the front page of the Drupal site.
   *
   * @var string
   */
  protected $baseUri;

  /**
   * URI of the test node created by CommentTestBase::setUp().
   *
   * @var string
   */
  protected $nodeUri;

  protected function setUp() {
    parent::setUp();

    // Enables anonymous user comments.
    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, [
      'access comments' => TRUE,
      'post comments' => TRUE,
      'skip comment approval' => TRUE,
    ]);
    // Allows anonymous to leave their contact information.
    $this->setCommentAnonymous(CommentInterface::ANONYMOUS_MAY_CONTACT);
    $this->setCommentPreview(DRUPAL_OPTIONAL);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);
    $this->setCommentSettings('comment_default_mode', CommentManagerInterface::COMMENT_MODE_THREADED, 'Comment paging changed.');

    // Prepares commonly used URIs.
    $this->baseUri = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $this->nodeUri = $this->node->toUrl('canonical', ['absolute' => TRUE])->toString();

    // Set relation between node and comment.
    $article_mapping = rdf_get_mapping('node', 'article');
    $comment_count_mapping = [
      'properties' => ['sioc:num_replies'],
      'datatype' => 'xsd:integer',
      'datatype_callback' => ['callable' => 'Drupal\rdf\CommonDataConverter::rawValue'],
    ];
    $article_mapping->setFieldMapping('comment_count', $comment_count_mapping)->save();

    // Save user mapping.
    $user_mapping = rdf_get_mapping('user', 'user');
    $username_mapping = [
      'properties' => ['foaf:name'],
    ];
    $user_mapping->setFieldMapping('name', $username_mapping)->save();
    $user_mapping->setFieldMapping('homepage', ['properties' => ['foaf:page'], 'mapping_type' => 'rel'])->save();

    // Save comment mapping.
    $mapping = rdf_get_mapping('comment', 'comment');
    $mapping->setBundleMapping(['types' => ['sioc:Post', 'sioct:Comment']])->save();
    $field_mappings = [
      'subject' => [
        'properties' => ['dc:title'],
      ],
      'created' => [
        'properties' => ['dc:date', 'dc:created'],
        'datatype' => 'xsd:dateTime',
        'datatype_callback' => ['callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value'],
      ],
      'changed' => [
        'properties' => ['dc:modified'],
        'datatype' => 'xsd:dateTime',
        'datatype_callback' => ['callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value'],
      ],
      'comment_body' => [
        'properties' => ['content:encoded'],
      ],
      'pid' => [
        'properties' => ['sioc:reply_of'],
        'mapping_type' => 'rel',
      ],
      'uid' => [
        'properties' => ['sioc:has_creator'],
        'mapping_type' => 'rel',
      ],
      'name' => [
        'properties' => ['foaf:name'],
      ],
    ];
    // Iterate over shared field mappings and save.
    foreach ($field_mappings as $field_name => $field_mapping) {
      $mapping->setFieldMapping($field_name, $field_mapping)->save();
    }
  }

  /**
   * Tests the presence of the RDFa markup for the number of comments.
   */
  public function testNumberOfCommentsRdfaMarkup() {
    // Posts 2 comments on behalf of registered user.
    $this->saveComment($this->node->id(), $this->webUser->id());
    $this->saveComment($this->node->id(), $this->webUser->id());

    // Tests number of comments in teaser view.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node');

    // Number of comments.
    $expected_value = [
      'type' => 'literal',
      'value' => 2,
      'datatype' => 'http://www.w3.org/2001/XMLSchema#integer',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $this->nodeUri, 'http://rdfs.org/sioc/ns#num_replies', $expected_value), 'Number of comments found in RDF output of teaser view (sioc:num_replies).');

    $this->drupalGet($this->node->toUrl());
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $this->nodeUri, 'http://rdfs.org/sioc/ns#num_replies', $expected_value), 'Number of comments found in RDF output of full node view mode (sioc:num_replies).');
  }

  /**
   * Tests comment author link markup has not been broken by RDF.
   */
  public function testCommentRdfAuthorMarkup() {
    // Post a comment as a registered user.
    $this->saveComment($this->node->id(), $this->webUser->id());

    // Give the user access to view user profiles so the profile link shows up.
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, ['access user profiles']);
    $this->drupalLogin($this->webUser);

    // Ensure that the author link still works properly after the author output
    // is modified by the RDF module.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertLink($this->webUser->getAccountName());
    $this->assertLinkByHref('user/' . $this->webUser->id());
  }

  /**
   * Tests if RDFa markup for meta information is present in comments.
   *
   * Tests presence of RDFa markup for the title, date and author and homepage
   * on comments from registered and anonymous users.
   */
  public function testCommentRdfaMarkup() {
    // Posts comment #1 on behalf of registered user.
    $comment1 = $this->saveComment($this->node->id(), $this->webUser->id());

    // Tests comment #1 with access to the user profile.
    $this->drupalLogin($this->webUser);
    $this->_testBasicCommentRdfaMarkup($comment1);

    // Tests comment #1 with no access to the user profile (as anonymous user).
    $this->drupalLogout();
    $this->_testBasicCommentRdfaMarkup($comment1);

    // Posts comment #2 as anonymous user.
    $anonymous_user = [];
    $anonymous_user['name'] = $this->randomMachineName();
    $anonymous_user['mail'] = 'tester@simpletest.org';
    $anonymous_user['homepage'] = 'http://example.org/';
    $comment2 = $this->saveComment($this->node->id(), 0, $anonymous_user);

    // Tests comment #2 as anonymous user.
    $this->_testBasicCommentRdfaMarkup($comment2, $anonymous_user);

    // Tests comment #2 as logged in user.
    $this->drupalLogin($this->webUser);
    $this->_testBasicCommentRdfaMarkup($comment2, $anonymous_user);
  }

  /**
   * Tests RDF comment replies.
   */
  public function testCommentReplyOfRdfaMarkup() {
    // Posts comment #1 on behalf of registered user.
    $this->drupalLogin($this->webUser);
    $comment_1 = $this->saveComment($this->node->id(), $this->webUser->id());

    $comment_1_uri = $comment_1->toUrl('canonical', ['absolute' => TRUE])->toString();

    // Posts a reply to the first comment.
    $comment_2 = $this->saveComment($this->node->id(), $this->webUser->id(), NULL, $comment_1->id());
    $comment_2_uri = $comment_2->toUrl('canonical', ['absolute' => TRUE])->toString();

    // Tests the reply_of relationship of a first level comment.
    $this->drupalGet($this->node->toUrl());
    $expected_value = [
      'type' => 'uri',
      'value' => $this->nodeUri,
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $comment_1_uri, 'http://rdfs.org/sioc/ns#reply_of', $expected_value), 'Comment relation to its node found in RDF output (sioc:reply_of).');

    // Tests the reply_of relationship of a second level comment.
    $expected_value = [
      'type' => 'uri',
      'value' => $this->nodeUri,
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $comment_2_uri, 'http://rdfs.org/sioc/ns#reply_of', $expected_value), 'Comment relation to its node found in RDF output (sioc:reply_of).');
    $expected_value = [
      'type' => 'uri',
      'value' => $comment_1_uri,
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $comment_2_uri, 'http://rdfs.org/sioc/ns#reply_of', $expected_value), 'Comment relation to its parent comment found in RDF output (sioc:reply_of).');
  }

  /**
   * Helper function for testCommentRdfaMarkup().
   *
   * Tests the current page for basic comment RDFa markup.
   *
   * @param $comment
   *   Comment object.
   * @param $account
   *   An array containing information about an anonymous user.
   */
  public function _testBasicCommentRdfaMarkup(CommentInterface $comment, $account = []) {
    $this->drupalGet($this->node->toUrl());
    $comment_uri = $comment->toUrl('canonical', ['absolute' => TRUE])->toString();

    // Comment type.
    $expected_value = [
      'type' => 'uri',
      'value' => 'http://rdfs.org/sioc/types#Comment',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $comment_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'Comment type found in RDF output (sioct:Comment).');
    // Comment type.
    $expected_value = [
      'type' => 'uri',
      'value' => 'http://rdfs.org/sioc/ns#Post',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $comment_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'Comment type found in RDF output (sioc:Post).');

    // Comment title.
    $expected_value = [
      'type' => 'literal',
      'value' => $comment->getSubject(),
      'lang' => 'en',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $comment_uri, 'http://purl.org/dc/terms/title', $expected_value), 'Comment subject found in RDF output (dc:title).');

    // Comment date.
    $expected_value = [
      'type' => 'literal',
      'value' => $this->container->get('date.formatter')->format($comment->getCreatedTime(), 'custom', 'c', 'UTC'),
      'datatype' => 'http://www.w3.org/2001/XMLSchema#dateTime',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $comment_uri, 'http://purl.org/dc/terms/date', $expected_value), 'Comment date found in RDF output (dc:date).');
    // Comment date.
    $expected_value = [
      'type' => 'literal',
      'value' => $this->container->get('date.formatter')->format($comment->getCreatedTime(), 'custom', 'c', 'UTC'),
      'datatype' => 'http://www.w3.org/2001/XMLSchema#dateTime',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $comment_uri, 'http://purl.org/dc/terms/created', $expected_value), 'Comment date found in RDF output (dc:created).');

    // Comment body.
    $expected_value = [
      'type' => 'literal',
      'value' => $comment->comment_body->value . "\n",
      'lang' => 'en',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $comment_uri, 'http://purl.org/rss/1.0/modules/content/encoded', $expected_value), 'Comment body found in RDF output (content:encoded).');

    // The comment author can be a registered user or an anonymous user.
    if ($comment->getOwnerId() > 0) {
      // Comment relation to author.
      $expected_value = [
        'type' => 'uri',
        'value' => Url::fromRoute('entity.user.canonical', ['user' => $comment->getOwnerId()], ['absolute' => TRUE])->toString(),
      ];
      $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $comment_uri, 'http://rdfs.org/sioc/ns#has_creator', $expected_value), 'Comment relation to author found in RDF output (sioc:has_creator).');
    }
    else {
      // The author is expected to be a blank node.
      $this->assertTrue($this->rdfElementIsBlankNode($this->getSession()->getPage()->getContent(), $this->baseUri, $comment_uri, '<http://rdfs.org/sioc/ns#has_creator>'));
    }

    // Author name.
    $name = empty($account["name"]) ? $this->webUser->getAccountName() : $account["name"] . " (not verified)";
    $expected_value = [
      'type' => 'literal',
      'value' => $name,
    ];
    $this->assertTrue($this->hasRdfChildProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $comment_uri, '<http://rdfs.org/sioc/ns#has_creator>', 'http://xmlns.com/foaf/0.1/name', $expected_value), 'Comment author name found in RDF output (foaf:name).');

    // Comment author homepage (only for anonymous authors).
    if ($comment->getOwnerId() == 0) {
      $expected_value = [
        'type' => 'uri',
        'value' => 'http://example.org/',
      ];
      $this->assertTrue($this->hasRdfChildProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $comment_uri, '<http://rdfs.org/sioc/ns#has_creator>', 'http://xmlns.com/foaf/0.1/page', $expected_value), 'Comment author link found in RDF output (foaf:page).');
    }
  }

  /**
   * Creates a comment entity.
   *
   * @param $nid
   *   Node id which will hold the comment.
   * @param $uid
   *   User id of the author of the comment. Can be NULL if $contact provided.
   * @param $contact
   *   Set to NULL for no contact info, TRUE to ignore success checking, and
   *   array of values to set contact info.
   * @param $pid
   *   Comment id of the parent comment in a thread.
   *
   * @return \Drupal\comment\Entity\Comment
   *   The saved comment.
   */
  public function saveComment($nid, $uid, $contact = NULL, $pid = 0) {
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
