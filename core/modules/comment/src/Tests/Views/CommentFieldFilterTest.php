<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Views\CommentFieldFilterTest.
 */

namespace Drupal\comment\Tests\Views;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests comment field filters with translations.
 *
 * @group comment
 */
class CommentFieldFilterTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('language');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_field_filters');

  /**
   * List of comment titles by language.
   *
   * @var array
   */
  public $comment_titles = array();

  function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['access comments']));

    // Add two new languages.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Make the comment body field translatable. The title is already
    // translatable by definition.
    $field = FieldStorageConfig::loadByName('comment', 'comment_body');
    $field->translatable = TRUE;
    $field->save();

    // Set up comment titles.
    $this->comment_titles = array(
      'en' => 'Food in Paris',
      'es' => 'Comida en Paris',
      'fr' => 'Nouriture en Paris',
    );

    // Create a new comment. Using the one created earlier will not work,
    // as it predates the language set-up.
    $comment = array(
      'uid' => $this->loggedInUser->id(),
      'entity_id' => $this->node_user_commented->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'cid' => '',
      'pid' => '',
      'node_type' => '',
    );
    $this->comment = entity_create('comment', $comment);

    // Add field values and translate the comment.
    $this->comment->subject->value = $this->comment_titles['en'];
    $this->comment->comment_body->value = $this->comment_titles['en'];
    $this->comment->langcode = 'en';
    $this->comment->save();
    foreach (array('es', 'fr') as $langcode) {
      $translation = $this->comment->addTranslation($langcode, array());
      $translation->comment_body->value = $this->comment_titles[$langcode];
      $translation->subject->value = $this->comment_titles[$langcode];
    }
    $this->comment->save();
  }

  /**
   * Tests body and title filters.
   */
  public function testFilters() {
    // Test the title filter page, which filters for title contains 'Comida'.
    // Should show just the Spanish translation, once.
    $this->assertPageCounts('test-title-filter', array('es' => 1, 'fr' => 0, 'en' => 0), 'Comida title filter');

    // Test the body filter page, which filters for body contains 'Comida'.
    // Should show just the Spanish translation, once.
    $this->assertPageCounts('test-body-filter', array('es' => 1, 'fr' => 0, 'en' => 0), 'Comida body filter');

    // Test the title Paris filter page, which filters for title contains
    // 'Paris'. Should show each translation once.
    $this->assertPageCounts('test-title-paris', array('es' => 1, 'fr' => 1, 'en' => 1), 'Paris title filter');

    // Test the body Paris filter page, which filters for body contains
    // 'Paris'. Should show each translation once.
    $this->assertPageCounts('test-body-paris', array('es' => 1, 'fr' => 1, 'en' => 1), 'Paris body filter');
  }

  /**
   * Asserts that the given comment translation counts are correct.
   *
   * @param string $path
   *   Path of the page to test.
   * @param array $counts
   *   Array whose keys are languages, and values are the number of times
   *   that translation should be shown on the given page.
   * @param string $message
   *   Message suffix to display.
   */
  protected function assertPageCounts($path, $counts, $message) {
    // Get the text of the page.
    $this->drupalGet($path);
    $text = $this->getTextContent();

    // Check the counts. Note that the title and body are both shown on the
    // page, and they are the same. So the title/body string should appear on
    // the page twice as many times as the input count.
    foreach ($counts as $langcode => $count) {
      $this->assertEqual(substr_count($text, $this->comment_titles[$langcode]), 2 * $count, 'Translation ' . $langcode . ' has count ' . $count . ' with ' . $message);
    }
  }
}
