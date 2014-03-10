<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentLanguageTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for comment language.
 */
class CommentLanguageTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * We also use the language_test module here to be able to turn on content
   * language negotiation. Drupal core does not provide a way in itself to do
   * that.
   *
   * @var array
   */
  public static $modules = array('node', 'language', 'language_test', 'comment_test');

  public static function getInfo() {
    return array(
      'name' => 'Comment language',
      'description' => 'Tests for comment language.',
      'group' => 'Comment',
    );
  }

  function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));

    // Create and login user.
    $admin_user = $this->drupalCreateUser(array('administer site configuration', 'administer languages', 'access administration pages', 'administer content types', 'administer comments', 'create article content', 'access comments', 'post comments', 'skip comment approval'));
    $this->drupalLogin($admin_user);

    // Add language.
    $edit = array('predefined_langcode' => 'fr');
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Set "Article" content type to use multilingual support.
    $edit = array('language_configuration[language_show]' => TRUE);
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, t('Save content type'));

    // Enable content language negotiation UI.
    \Drupal::state()->set('language_test.content_language_type', TRUE);

    // Set interface language detection to user and content language detection
    // to URL. Disable inheritance from interface language to ensure content
    // language will fall back to the default language if no URL language can be
    // detected.
    $edit = array(
      'language_interface[enabled][language-user]' => TRUE,
      'language_content[enabled][language-url]' => TRUE,
      'language_content[enabled][language-interface]' => FALSE,
    );
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Change user language preference, this way interface language is always
    // French no matter what path prefix the URLs have.
    $edit = array('preferred_langcode' => 'fr');
    $this->drupalPostForm("user/" . $admin_user->id() . "/edit", $edit, t('Save'));

    // Create comment field on article.
    $this->container->get('comment.manager')->addDefaultField('node', 'article');

    // Make comment body translatable.
    $field = field_info_field('comment', 'comment_body');
    $field->translatable = TRUE;
    $field->save();
    $this->assertTrue($field->isTranslatable(), 'Comment body is translatable.');
  }

  /**
   * Test that comment language is properly set.
   */
  function testCommentLanguage() {

    // Create two nodes, one for english and one for french, and comment each
    // node using both english and french as content language by changing URL
    // language prefixes. Meanwhile interface language is always French, which
    // is the user language preference. This way we can ensure that node
    // language and interface language do not influence comment language, as
    // only content language has to.
    foreach (language_list() as $node_langcode => $node_language) {
      // Create "Article" content.
      $title = $this->randomName();
      $edit = array(
        'title[0][value]' => $title,
        'body[0][value]' => $this->randomName(),
        'langcode' => $node_langcode,
        'comment[0][status]' => CommentItemInterface::OPEN,
      );
      $this->drupalPostForm("node/add/article", $edit, t('Save'));
      $node = $this->drupalGetNodeByTitle($title);

      $prefixes = language_negotiation_url_prefixes();
      foreach (language_list() as $langcode => $language) {
        // Post a comment with content language $langcode.
        $prefix = empty($prefixes[$langcode]) ? '' : $prefixes[$langcode] . '/';
        $comment_values[$node_langcode][$langcode] = $this->randomName();
        $edit = array(
          'subject' => $this->randomName(),
          'comment_body[0][value]' => $comment_values[$node_langcode][$langcode],
        );
        $this->drupalPostForm($prefix . 'node/' . $node->id(), $edit, t('Preview'));
        $this->drupalPostForm(NULL, $edit, t('Save'));

        // Check that comment language matches the current content language.
        $cid = db_select('comment', 'c')
          ->fields('c', array('cid'))
          ->condition('entity_id', $node->id())
          ->condition('entity_type', 'node')
          ->condition('field_id', 'node__comment')
          ->orderBy('cid', 'DESC')
          ->range(0, 1)
          ->execute()
          ->fetchField();
        $comment = comment_load($cid);
        $args = array('%node_language' => $node_langcode, '%comment_language' => $comment->langcode->value, '%langcode' => $langcode);
        $this->assertEqual($comment->langcode->value, $langcode, format_string('The comment posted with content language %langcode and belonging to the node with language %node_language has language %comment_language', $args));
        $this->assertEqual($comment->comment_body->value, $comment_values[$node_langcode][$langcode], 'Comment body correctly stored.');
      }
    }

    // Check that comment bodies appear in the administration UI.
    $this->drupalGet('admin/content/comment');
    foreach ($comment_values as $node_values) {
      foreach ($node_values as $value) {
        $this->assertRaw($value);
      }
    }
  }

}
