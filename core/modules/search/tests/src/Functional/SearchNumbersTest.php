<?php

namespace Drupal\Tests\search\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests that numbers can be searched.
 *
 * @group search
 */
class SearchNumbersTest extends BrowserTestBase {

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
   * An array containing a series of "numbers" for testing purposes.
   *
   * Create content with various numbers in it.
   * Note: 50 characters is the current limit of the search index's word
   * field.
   *
   * @var string[]
   */
  protected $numbers = [
    'ISBN' => '978-0446365383',
    'UPC' => '036000 291452',
    'EAN bar code' => '5901234123457',
    'negative' => '-123456.7890',
    'quoted negative' => '"-123456.7890"',
    'leading zero' => '0777777777',
    'tiny' => '111',
    'small' => '22222222222222',
    'medium' => '333333333333333333333333333',
    'large' => '444444444444444444444444444444444444444',
    'gigantic' => '5555555555555555555555555555555555555555555555555',
    'over fifty characters' => '666666666666666666666666666666666666666666666666666666666666',
    'date' => '01/02/2009',
    'commas' => '987,654,321',
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

    foreach ($this->numbers as $doc => $num) {
      $info = [
        'body' => [['value' => $num]],
        'type' => 'page',
        'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        'title' => $doc . ' number',
      ];
      $this->nodes[$doc] = $this->drupalCreateNode($info);
    }

    // Run cron to ensure the content is indexed.
    $this->cronRun();
    $this->drupalGet('admin/reports/dblog');
    $this->assertSession()->pageTextContains('Cron run completed');
  }

  /**
   * Tests that all the numbers can be searched.
   */
  public function testNumberSearching() {
    $types = array_keys($this->numbers);

    foreach ($types as $type) {
      $number = $this->numbers[$type];
      // If the number is negative, remove the - sign, because - indicates
      // "not keyword" when searching.
      $number = ltrim($number, '-');
      $node = $this->nodes[$type];

      // Verify that the node title does not appear on the search page
      // with a dummy search.
      $this->drupalGet('search/node');
      $this->submitForm(['keys' => 'foo'], 'Search');
      $this->assertSession()->pageTextNotContains($node->label());

      // Verify that the node title does appear as a link on the search page
      // when searching for the number.
      $this->drupalGet('search/node');
      $this->submitForm(['keys' => $number], 'Search');
      $this->assertSession()->pageTextContains($node->label());
    }
  }

}
