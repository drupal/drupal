<?php

namespace Drupal\Tests\book\FunctionalJavascript;

use Behat\Mink\Exception\ExpectationException;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;

/**
 * Tests Book javascript functionality.
 *
 * @group book
 */
class BookJavascriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['book'];

  /**
   * Tests re-ordering of books.
   */
  public function testBookOrdering() {
    $book = Node::create([
      'type' => 'book',
      'title' => 'Book',
      'book' => ['bid' => 'new'],
    ]);
    $book->save();
    $page1 = Node::create([
      'type' => 'book',
      'title' => '1st page',
      'book' => ['bid' => $book->id(), 'pid' => $book->id(), 'weight' => 0],
    ]);
    $page1->save();
    $page2 = Node::create([
      'type' => 'book',
      'title' => '2nd page',
      'book' => ['bid' => $book->id(), 'pid' => $book->id(), 'weight' => 1],
    ]);
    $page2->save();

    // Head to admin screen and attempt to re-order.
    $this->drupalLogin($this->drupalCreateUser(['administer book outlines']));
    $this->drupalGet('admin/structure/book/' . $book->id());

    $page = $this->getSession()->getPage();

    $weight_select1 = $page->findField("table[book-admin-{$page1->id()}][weight]");
    $weight_select2 = $page->findField("table[book-admin-{$page2->id()}][weight]");

    // Check that rows weight selects are hidden.
    $this->assertFalse($weight_select1->isVisible());
    $this->assertFalse($weight_select2->isVisible());

    // Check that '2nd page' row is heavier than '1st page' row.
    $this->assertGreaterThan($weight_select1->getValue(), $weight_select2->getValue());

    // Check that '1st page' precedes the '2nd page'.
    $this->assertOrderInPage(['1st page', '2nd page']);

    // Check that the 'unsaved changes' text is not present in the message area.
    $this->assertSession()->pageTextNotContains('You have unsaved changes.');

    // Drag and drop the '1st page' row over the '2nd page' row.
    // @todo: Test also the reverse, '2nd page' over '1st page', when
    //   https://www.drupal.org/node/2769825 is fixed.
    // @see https://www.drupal.org/node/2769825
    $dragged = $this->xpath("//tr[@data-drupal-selector='edit-table-book-admin-{$page1->id()}']//a[@class='tabledrag-handle']")[0];
    $target = $this->xpath("//tr[@data-drupal-selector='edit-table-book-admin-{$page2->id()}']//a[@class='tabledrag-handle']")[0];
    $dragged->dragTo($target);

    // Give javascript some time to manipulate the DOM.
    $this->assertJsCondition('jQuery(".tabledrag-changed-warning").is(":visible")');

    // Check that the 'unsaved changes' text appeared in the message area.
    $this->assertSession()->pageTextContains('You have unsaved changes.');

    // Check that '2nd page' page precedes the '1st page'.
    $this->assertOrderInPage(['2nd page', '1st page']);

    $this->submitForm([], 'Save book pages');
    $this->assertSession()->pageTextContains(new FormattableMarkup('Updated book @book.', ['@book' => $book->getTitle()]));

    // Check that page reordering was done in the backend for drag-n-drop.
    $page1 = Node::load($page1->id());
    $page2 = Node::load($page2->id());
    $this->assertGreaterThan($page2->book['weight'], $page1->book['weight']);

    // Check again that '2nd page' is on top after form submit in the UI.
    $this->assertOrderInPage(['2nd page', '1st page']);

    // Toggle row weight selects as visible.
    $page->findButton('Show row weights')->click();

    // Check that rows weight selects are visible.
    $this->assertTrue($weight_select1->isVisible());
    $this->assertTrue($weight_select2->isVisible());

    // Check that '1st page' row became heavier than '2nd page' row.
    $this->assertGreaterThan($weight_select2->getValue(), $weight_select1->getValue());

    // Reverse again using the weight fields. Use the current values so the test
    // doesn't rely on knowing the values in the select boxes.
    $value1 = $weight_select1->getValue();
    $value2 = $weight_select2->getValue();
    $weight_select1->setValue($value2);
    $weight_select2->setValue($value1);

    // Toggle row weight selects back to hidden.
    $page->findButton('Hide row weights')->click();

    // Check that rows weight selects are hidden again.
    $this->assertFalse($weight_select1->isVisible());
    $this->assertFalse($weight_select2->isVisible());

    $this->submitForm([], 'Save book pages');
    $this->assertSession()->pageTextContains(new FormattableMarkup('Updated book @book.', ['@book' => $book->getTitle()]));

    // Check that the '1st page' is first again.
    $this->assertOrderInPage(['1st page', '2nd page']);

    // Check that page reordering was done in the backend for manual weight
    // field usage.
    $page1 = Node::load($page1->id());
    $page2 = Node::load($page2->id());
    $this->assertGreaterThan($page2->book['weight'], $page1->book['weight']);
  }

  /**
   * Asserts that several pieces of markup are in a given order in the page.
   *
   * @param string[] $items
   *   An ordered list of strings.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When any of the given string is not found.
   *
   * @todo Remove this once https://www.drupal.org/node/2817657 is committed.
   */
  protected function assertOrderInPage(array $items) {
    $session = $this->getSession();
    $text = $session->getPage()->getHtml();
    $strings = [];
    foreach ($items as $item) {
      if (($pos = strpos($text, $item)) === FALSE) {
        throw new ExpectationException("Cannot find '$item' in the page", $session->getDriver());
      }
      $strings[$pos] = $item;
    }
    ksort($strings);
    $ordered = implode(', ', array_map(function ($item) {
      return "'$item'";
    }, $items));
    $this->assertSame($items, array_values($strings), "Found strings, ordered as: $ordered.");
  }

}
