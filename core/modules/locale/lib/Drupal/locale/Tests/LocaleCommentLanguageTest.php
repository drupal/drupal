<?php

/**
 * @file
 * Definition of Drupal\locale\Tests\LocaleCommentLanguageTest.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for comment language.
 */
class LocaleCommentLanguageTest extends WebTestBase {
  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'Comment language',
      'description' => 'Tests for comment language.',
      'group' => 'Locale',
    );
  }

  function setUp() {
    // We also use language_test module here to be able to turn on content
    // language negotiation. Drupal core does not provide a way in itself
    // to do that.
    parent::setUp('locale', 'language_test');

    // Create and login user.
    $admin_user = $this->drupalCreateUser(array('administer site configuration', 'administer languages', 'access administration pages', 'administer content types', 'create article content'));
    $this->drupalLogin($admin_user);

    // Add language.
    $edit = array('predefined_langcode' => 'fr');
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));

    // Set "Article" content type to use multilingual support.
    $edit = array('node_type_language' => 1);
    $this->drupalPost('admin/structure/types/manage/article', $edit, t('Save content type'));

    // Enable content language negotiation UI.
    variable_set('language_test_content_language_type', TRUE);

    // Set interface language detection to user and content language detection
    // to URL. Disable inheritance from interface language to ensure content
    // language will fall back to the default language if no URL language can be
    // detected.
    $edit = array(
      'language_interface[enabled][language-user]' => TRUE,
      'language_content[enabled][language-url]' => TRUE,
      'language_content[enabled][language-interface]' => FALSE,
    );
    $this->drupalPost('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Change user language preference, this way interface language is always
    // French no matter what path prefix the URLs have.
    $edit = array('preferred_langcode' => 'fr');
    $this->drupalPost("user/{$admin_user->uid}/edit", $edit, t('Save'));
  }

  /**
   * Test that comment language is properly set.
   */
  function testCommentLanguage() {
    drupal_static_reset('language_list');

    // Create two nodes, one for english and one for french, and comment each
    // node using both english and french as content language by changing URL
    // language prefixes. Meanwhile interface language is always French, which
    // is the user language preference. This way we can ensure that node
    // language and interface language do not influence comment language, as
    // only content language has to.
    foreach (language_list() as $node_langcode => $node_language) {
      $langcode_not_specified = LANGUAGE_NOT_SPECIFIED;

      // Create "Article" content.
      $title = $this->randomName();
      $edit = array(
        "title" => $title,
        "body[$langcode_not_specified][0][value]" => $this->randomName(),
        "langcode" => $node_langcode,
      );
      $this->drupalPost("node/add/article", $edit, t('Save'));
      $node = $this->drupalGetNodeByTitle($title);

      $prefixes = language_negotiation_url_prefixes();
      foreach (language_list() as $langcode => $language) {
        // Post a comment with content language $langcode.
        $prefix = empty($prefixes[$langcode]) ? '' : $prefixes[$langcode] . '/';
        $edit = array("comment_body[$langcode_not_specified][0][value]" => $this->randomName());
        $this->drupalPost("{$prefix}node/{$node->nid}", $edit, t('Save'));

        // Check that comment language matches the current content language.
        $comment = db_select('comment', 'c')
          ->fields('c')
          ->condition('nid', $node->nid)
          ->orderBy('cid', 'DESC')
          ->execute()
          ->fetchObject();
        $args = array('%node_language' => $node_langcode, '%comment_language' => $comment->langcode, '%langcode' => $langcode);
        $this->assertEqual($comment->langcode, $langcode, t('The comment posted with content language %langcode and belonging to the node with language %node_language has language %comment_language', $args));
      }
    }
  }
}
