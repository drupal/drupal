<?php

namespace Drupal\Tests\search\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests that numbers can be searched with more complex matching.
 *
 * @group search
 */
class SearchNumberMatchingTest extends BrowserTestBase {

  use CronRunTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['dblog', 'node', 'search'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to administer nodes.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * An array of strings containing numbers to use for testing.
   *
   * Define a group of numbers that should all match each other --
   * numbers with internal punctuation should match each other, as well
   * as numbers with and without leading zeros and leading/trailing
   * . and -.
   *
   * @var string[]
   */
  protected $numbers = [
    '123456789',
    '12/34/56789',
    '12.3456789',
    '12-34-56789',
    '123,456,789',
    '-123456789',
    '0123456789',
  ];

  /**
   * An array of nodes created for testing purposes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $this->testUser = $this->drupalCreateUser([
      'search content',
      'access content',
      'administer nodes',
      'access site reports',
    ]);
    $this->drupalLogin($this->testUser);

    foreach ($this->numbers as $num) {
      $info = [
        'body' => [['value' => $num]],
        'type' => 'page',
        'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ];
      $this->nodes[] = $this->drupalCreateNode($info);
    }

    // Run cron to ensure the content is indexed.
    $this->cronRun();
    $this->drupalGet('admin/reports/dblog');
    $this->assertText('Cron run completed');
  }

  /**
   * Tests that all the numbers can be searched.
   */
  public function testNumberSearching() {
    for ($i = 0; $i < count($this->numbers); $i++) {
      $node = $this->nodes[$i];

      // Verify that the node title does not appear on the search page
      // with a dummy search.
      $this->drupalPostForm('search/node', ['keys' => 'foo'], 'Search');
      $this->assertNoText($node->label());

      // Now verify that we can find node i by searching for any of the
      // numbers.
      for ($j = 0; $j < count($this->numbers); $j++) {
        $number = $this->numbers[$j];
        // If the number is negative, remove the - sign, because - indicates
        // "not keyword" when searching.
        $number = ltrim($number, '-');

        $this->drupalPostForm('search/node', ['keys' => $number], 'Search');
        $this->assertText($node->label());
      }
    }

  }

}
