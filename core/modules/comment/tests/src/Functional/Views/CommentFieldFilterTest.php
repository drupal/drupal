<?php

namespace Drupal\Tests\comment\Functional\Views;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\comment\Entity\Comment;

/**
 * Tests comment field filters with translations.
 *
 * @group comment
 */
class CommentFieldFilterTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_field_filters'];

  /**
   * List of comment titles by language.
   *
   * @var array
   */
  public $commentTitles = [];

  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);
    $this->drupalLogin($this->drupalCreateUser(['access comments']));

    // Add two new languages.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Set up comment titles.
    $this->commentTitles = [
      'en' => 'Food in Paris',
      'es' => 'Comida en Paris',
      'fr' => 'Nourriture en Paris',
    ];

    // Create a new comment. Using the one created earlier will not work,
    // as it predates the language set-up.
    $comment = [
      'uid' => $this->loggedInUser->id(),
      'entity_id' => $this->nodeUserCommented->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'cid' => '',
      'pid' => '',
      'node_type' => '',
    ];
    $this->comment = Comment::create($comment);

    // Add field values and translate the comment.
    $this->comment->subject->value = $this->commentTitles['en'];
    $this->comment->comment_body->value = $this->commentTitles['en'];
    $this->comment->langcode = 'en';
    $this->comment->save();
    foreach (['es', 'fr'] as $langcode) {
      $translation = $this->comment->addTranslation($langcode, []);
      $translation->comment_body->value = $this->commentTitles[$langcode];
      $translation->subject->value = $this->commentTitles[$langcode];
    }
    $this->comment->save();
  }

  /**
   * Tests body and title filters.
   */
  public function testFilters() {
    // Test the title filter page, which filters for title contains 'Comida'.
    // Should show just the Spanish translation, once.
    $this->assertPageCounts('test-title-filter', ['es' => 1, 'fr' => 0, 'en' => 0], 'Comida title filter');

    // Test the body filter page, which filters for body contains 'Comida'.
    // Should show just the Spanish translation, once.
    $this->assertPageCounts('test-body-filter', ['es' => 1, 'fr' => 0, 'en' => 0], 'Comida body filter');

    // Test the title Paris filter page, which filters for title contains
    // 'Paris'. Should show each translation once.
    $this->assertPageCounts('test-title-paris', ['es' => 1, 'fr' => 1, 'en' => 1], 'Paris title filter');

    // Test the body Paris filter page, which filters for body contains
    // 'Paris'. Should show each translation once.
    $this->assertPageCounts('test-body-paris', ['es' => 1, 'fr' => 1, 'en' => 1], 'Paris body filter');
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
   *
   * @internal
   */
  protected function assertPageCounts(string $path, array $counts, string $message): void {
    // Get the text of the page.
    $this->drupalGet($path);
    $text = $this->getTextContent();

    // Check the counts. Note that the title and body are both shown on the
    // page, and they are the same. So the title/body string should appear on
    // the page twice as many times as the input count.
    foreach ($counts as $langcode => $count) {
      $this->assertEquals(2 * $count, substr_count($text, $this->commentTitles[$langcode]), 'Translation ' . $langcode . ' has count ' . $count . ' with ' . $message);
    }
  }

}
