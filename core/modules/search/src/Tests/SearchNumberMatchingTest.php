<?php

namespace Drupal\search\Tests;

use Drupal\Core\Language\LanguageInterface;

/**
 * Tests that numbers can be searched with more complex matching.
 *
 * @group search
 */
class SearchNumberMatchingTest extends SearchTestBase {
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
  protected $numbers = array(
    '123456789',
    '12/34/56789',
    '12.3456789',
    '12-34-56789',
    '123,456,789',
    '-123456789',
    '0123456789',
  );

  /**
   * An array of nodes created for testing purposes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  protected function setUp() {
    parent::setUp();

    $this->testUser = $this->drupalCreateUser(array('search content', 'access content', 'administer nodes', 'access site reports'));
    $this->drupalLogin($this->testUser);

    foreach ($this->numbers as $num) {
      $info = array(
        'body' => array(array('value' => $num)),
        'type' => 'page',
        'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      );
      $this->nodes[] = $this->drupalCreateNode($info);
    }

    // Run cron to ensure the content is indexed.
    $this->cronRun();
    $this->drupalGet('admin/reports/dblog');
    $this->assertText(t('Cron run completed'), 'Log shows cron run completed');
  }

  /**
   * Tests that all the numbers can be searched.
   */
  function testNumberSearching() {
    for ($i = 0; $i < count($this->numbers); $i++) {
      $node = $this->nodes[$i];

      // Verify that the node title does not appear on the search page
      // with a dummy search.
      $this->drupalPostForm('search/node',
        array('keys' => 'foo'),
        t('Search'));
      $this->assertNoText($node->label(), format_string('%number: node title not shown in dummy search', array('%number' => $i)));

      // Now verify that we can find node i by searching for any of the
      // numbers.
      for ($j = 0; $j < count($this->numbers); $j++) {
        $number = $this->numbers[$j];
        // If the number is negative, remove the - sign, because - indicates
        // "not keyword" when searching.
        $number = ltrim($number, '-');

        $this->drupalPostForm('search/node',
          array('keys' => $number),
          t('Search'));
        $this->assertText($node->label(), format_string('%i: node title shown (search found the node) in search for number %number', array('%i' => $i, '%number' => $number)));
      }
    }

  }

}
