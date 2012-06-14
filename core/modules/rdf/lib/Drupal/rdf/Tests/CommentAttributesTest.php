<?php

/**
 * @file
 * Definition of Drupal\rdf\Tests\CommentAttributesTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\comment\Tests\CommentTestBase;

/**
 * Tests the RDFa markup of comments.
 */
class CommentAttributesTest extends CommentTestBase {

  public static function getInfo() {
    return array(
      'name' => 'RDF comment mapping',
      'description' => 'Tests the RDFa markup of comments.',
      'group' => 'RDF',
    );
  }

  public function setUp() {
    parent::setUp('comment', 'rdf', 'rdf_test');

    $this->admin_user = $this->drupalCreateUser(array('administer content types', 'administer comments', 'administer permissions', 'administer blocks'));
    $this->web_user = $this->drupalCreateUser(array('access comments', 'post comments', 'create article content', 'access user profiles'));

    // Enables anonymous user comments.
    user_role_change_permissions(DRUPAL_ANONYMOUS_RID, array(
      'access comments' => TRUE,
      'post comments' => TRUE,
      'skip comment approval' => TRUE,
    ));
    // Allows anonymous to leave their contact information.
    $this->setCommentAnonymous(COMMENT_ANONYMOUS_MAY_CONTACT);
    $this->setCommentPreview(DRUPAL_OPTIONAL);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);
    $this->setCommentSettings('comment_default_mode', COMMENT_MODE_THREADED, t('Comment paging changed.'));

    // Creates the nodes on which the test comments will be posted.
    $this->drupalLogin($this->web_user);
    $this->node1 = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1));
    $this->node2 = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1));
    $this->drupalLogout();
  }

  /**
   * Tests the presence of the RDFa markup for the number of comments.
   */
  public function testNumberOfCommentsRdfaMarkup() {
    // Posts 2 comments as a registered user.
    $this->drupalLogin($this->web_user);
    $this->postComment($this->node1, $this->randomName(), $this->randomName());
    $this->postComment($this->node1, $this->randomName(), $this->randomName());

    // Tests number of comments in teaser view.
    $this->drupalGet('node');
    $comment_count_teaser = $this->xpath('//div[contains(@typeof, "sioc:Item")]//li[contains(@class, "comment-comments")]/a[contains(@property, "sioc:num_replies") and contains(@content, "2") and @datatype="xsd:integer"]');
    $this->assertTrue(!empty($comment_count_teaser), t('RDFa markup for the number of comments found on teaser view.'));
    $comment_count_link = $this->xpath('//div[@about=:url]//a[contains(@property, "sioc:num_replies") and @rel=""]', array(':url' => url("node/{$this->node1->nid}")));
    $this->assertTrue(!empty($comment_count_link), t('Empty rel attribute found in comment count link.'));

    // Tests number of comments in full node view.
    $this->drupalGet('node/' . $this->node1->nid);
    $node_url = url('node/' . $this->node1->nid);
    $comment_count_teaser = $this->xpath('/html/head/meta[@about=:node-url and @property="sioc:num_replies" and @content="2" and @datatype="xsd:integer"]', array(':node-url' => $node_url));
    $this->assertTrue(!empty($comment_count_teaser), t('RDFa markup for the number of comments found on full node view.'));
  }

  /**
   * Tests the presence of the RDFa markup for the title, date and author and
   * homepage on registered users and anonymous comments.
   */
  public function testCommentRdfaMarkup() {

    // Posts comment #1 as a registered user.
    $this->drupalLogin($this->web_user);
    $comment1_subject = $this->randomName();
    $comment1_body = $this->randomName();
    $comment1 = $this->postComment($this->node1, $comment1_body, $comment1_subject);

    // Tests comment #1 with access to the user profile.
    $this->drupalGet('node/' . $this->node1->nid);
    $this->_testBasicCommentRdfaMarkup($comment1);

    // Tests comment #1 with no access to the user profile (as anonymous user).
    $this->drupalLogout();
    $this->drupalGet('node/' . $this->node1->nid);
    $this->_testBasicCommentRdfaMarkup($comment1);

    // Posts comment #2 as anonymous user.
    $comment2_subject = $this->randomName();
    $comment2_body = $this->randomName();
    $anonymous_user = array();
    $anonymous_user['name'] = $this->randomName();
    $anonymous_user['mail'] = 'tester@simpletest.org';
    $anonymous_user['homepage'] = 'http://example.org/';
    $comment2 = $this->postComment($this->node2, $comment2_body, $comment2_subject, $anonymous_user);
    $this->drupalGet('node/' . $this->node2->nid);

    // Tests comment #2 as anonymous user.
    $this->_testBasicCommentRdfaMarkup($comment2, $anonymous_user);
    // Tests the RDFa markup for the homepage (specific to anonymous comments).
    $comment_homepage = $this->xpath('//div[contains(@class, "comment") and contains(@typeof, "sioct:Comment")]//span[@rel="sioc:has_creator"]/a[contains(@class, "username") and @typeof="sioc:UserAccount" and @property="foaf:name" and @href="http://example.org/" and contains(@rel, "foaf:page")]');
    $this->assertTrue(!empty($comment_homepage), t('RDFa markup for the homepage of anonymous user found.'));
    // There should be no about attribute on anonymous comments.
    $comment_homepage = $this->xpath('//div[contains(@class, "comment") and contains(@typeof, "sioct:Comment")]//span[@rel="sioc:has_creator"]/a[@about]');
    $this->assertTrue(empty($comment_homepage), t('No about attribute is present on anonymous user comment.'));

    // Tests comment #2 as logged in user.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('node/' . $this->node2->nid);
    $this->_testBasicCommentRdfaMarkup($comment2, $anonymous_user);
    // Tests the RDFa markup for the homepage (specific to anonymous comments).
    $comment_homepage = $this->xpath('//div[contains(@class, "comment") and contains(@typeof, "sioct:Comment")]//span[@rel="sioc:has_creator"]/a[contains(@class, "username") and @typeof="sioc:UserAccount" and @property="foaf:name" and @href="http://example.org/" and contains(@rel, "foaf:page")]');
    $this->assertTrue(!empty($comment_homepage), t("RDFa markup for the homepage of anonymous user found."));
    // There should be no about attribute on anonymous comments.
    $comment_homepage = $this->xpath('//div[contains(@class, "comment") and contains(@typeof, "sioct:Comment")]//span[@rel="sioc:has_creator"]/a[@about]');
    $this->assertTrue(empty($comment_homepage), t("No about attribute is present on anonymous user comment."));
  }

  /**
   * Test RDF comment replies.
   */
  public function testCommentReplyOfRdfaMarkup() {
    // Posts comment #1 as a registered user.
    $this->drupalLogin($this->web_user);
    $comments[] = $this->postComment($this->node1, $this->randomName(), $this->randomName());

    // Tests the reply_of relationship of a first level comment.
    $result = $this->xpath("(id('comments')//div[contains(@class,'comment ')])[position()=1]//span[@rel='sioc:reply_of' and @resource=:node]", array(':node' => url("node/{$this->node1->nid}")));
    $this->assertEqual(1, count($result), t('RDFa markup referring to the node is present.'));
    $result = $this->xpath("(id('comments')//div[contains(@class,'comment ')])[position()=1]//span[@rel='sioc:reply_of' and @resource=:comment]", array(':comment' => url('comment/1#comment-1')));
    $this->assertFalse($result, t('No RDFa markup referring to the comment itself is present.'));

    // Posts a reply to the first comment.
    $this->drupalGet('comment/reply/' . $this->node1->nid . '/' . $comments[0]->id);
    $comments[] = $this->postComment(NULL, $this->randomName(), $this->randomName(), TRUE);

    // Tests the reply_of relationship of a second level comment.
    $result = $this->xpath("(id('comments')//div[contains(@class,'comment ')])[position()=2]//span[@rel='sioc:reply_of' and @resource=:node]", array(':node' => url("node/{$this->node1->nid}")));
    $this->assertEqual(1, count($result), t('RDFa markup referring to the node is present.'));
    $result = $this->xpath("(id('comments')//div[contains(@class,'comment ')])[position()=2]//span[@rel='sioc:reply_of' and @resource=:comment]", array(':comment' => url('comment/1', array('fragment' => 'comment-1'))));
    $this->assertEqual(1, count($result), t('RDFa markup referring to the parent comment is present.'));
    $comments = $this->xpath("(id('comments')//div[contains(@class,'comment ')])[position()=2]");
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
  function _testBasicCommentRdfaMarkup($comment, $account = array()) {
    $comment_container = $this->xpath('//div[contains(@class, "comment") and contains(@typeof, "sioct:Comment")]');
    $this->assertTrue(!empty($comment_container), t("Comment RDF type for comment found."));
    $comment_title = $this->xpath('//div[contains(@class, "comment") and contains(@typeof, "sioct:Comment")]//h3[@property="dc:title"]');
    $this->assertEqual((string)$comment_title[0]->a, $comment->subject, t("RDFa markup for the comment title found."));
    $comment_date = $this->xpath('//div[contains(@class, "comment") and contains(@typeof, "sioct:Comment")]//*[contains(@property, "dc:date") and contains(@property, "dc:created")]');
    $this->assertTrue(!empty($comment_date), t("RDFa markup for the date of the comment found."));
    // The author tag can be either a or span
    $comment_author = $this->xpath('//div[contains(@class, "comment") and contains(@typeof, "sioct:Comment")]//span[@rel="sioc:has_creator"]/*[contains(@class, "username") and @typeof="sioc:UserAccount" and @property="foaf:name"]');
    $name = empty($account["name"]) ? $this->web_user->name : $account["name"] . " (not verified)";
    $this->assertEqual((string)$comment_author[0], $name, t("RDFa markup for the comment author found."));
    $comment_body = $this->xpath('//div[contains(@class, "comment") and contains(@typeof, "sioct:Comment")]//div[@class="content"]//div[contains(@class, "comment-body")]//div[@property="content:encoded"]');
    $this->assertEqual((string)$comment_body[0]->p, $comment->comment, t("RDFa markup for the comment body found."));
  }
}
