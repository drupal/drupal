<?php

declare(strict_types=1);

namespace Drupal\Tests\views\FunctionalJavascript\Plugin\views\Handler;

use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests the add filter handler UI.
 *
 * @group views
 */
class FilterTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views', 'views_ui', 'user'];

  /**
   * The account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Disable automatic live preview to make the sequence of calls clearer.
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.always_live_preview', FALSE)->save();

    $this->account = $this->drupalCreateUser(['administer views']);
    $this->drupalLogin($this->account);

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'body',
      'bundle' => 'page',
    ])->save();
  }

  /**
   * Tests adding a filter handler.
   */
  public function testAddingFilter(): void {
    $web_assert = $this->assertSession();

    $url = '/admin/structure/views/view/content';
    $this->drupalGet($url);

    $page = $this->getSession()->getPage();

    // Open the 'Add filter dialog'.
    $page->clickLink('views-add-filter');

    // Test filtering by type.
    $web_assert->waitForField('override[controls][group]');
    $page->fillField('override[controls][group]', 'content');
    $only_content_rows = $this->waitForOnlyContentRows();
    $this->assertTrue($only_content_rows);

    // Search for a specific title and test that this is now the only one shown.
    $page->fillField('override[controls][options_search]', 'body (body)');
    $filtering_done = $this->waitForVisibleElementCount(1, 'tr.filterable-option');
    $this->assertTrue($filtering_done);

    // Select the body field and apply the choice.
    $page->checkField('name[node__body.body_value]');
    $page->find('css', '.ui-dialog .ui-dialog-buttonpane')->pressButton('Add and configure filter criteria');
    $web_assert->waitForField('options[expose_button][checkbox][checkbox]');

    // Expose the filter.
    $page->findField('options[expose_button][checkbox][checkbox]')->click();
    $web_assert->waitForField('options[expose][label]');
    $page->find('css', '.ui-dialog .ui-dialog-buttonpane')->pressButton('Apply');
    $web_assert->waitForText('Content: body (exposed)');
    $web_assert->responseContains('Content: body (exposed)');
  }

  /**
   * Removes any non-visible elements from the passed array.
   *
   * @param \Behat\Mink\Element\NodeElement[] $elements
   *   The elements to filter.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The filtered elements.
   */
  protected function filterVisibleElements($elements) {
    $elements = array_filter($elements, function ($element) {
      return $element->isVisible();
    });
    return $elements;
  }

  /**
   * Waits for the specified number of items to be visible.
   *
   * @param int $count
   *   The number of found elements to wait for.
   * @param string|array $locator
   *   The selector locator.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   *
   * @return bool
   *   TRUE if the required number was matched, FALSE otherwise.
   */
  protected function waitForVisibleElementCount($count, $locator, $timeout = 10000) {
    $page = $this->getSession()->getPage();

    return $page->waitFor($timeout / 1000, function () use ($count, $page, $locator) {
      $elements = $page->findAll('css', $locator);
      $visible_elements = $this->filterVisibleElements($elements);
      if (count($visible_elements) === $count) {
        return TRUE;
      }
      return FALSE;
    });
  }

  /**
   * Waits for only content rows to be visible.
   *
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   *
   * @return bool
   *   TRUE if the required number was matched, FALSE otherwise.
   */
  protected function waitForOnlyContentRows($timeout = 10000) {
    $page = $this->getSession()->getPage();

    return $page->waitFor($timeout / 1000, function () use ($page) {
      $handler_rows = $page->findAll('css', 'tr.filterable-option');
      $handler_rows = $this->filterVisibleElements($handler_rows);

      foreach ($handler_rows as $handler_row) {
        // Test that all the visible rows are of the 'content' type.
        if (!str_contains($handler_row->getAttribute('class'), 'content')) {
          return FALSE;
        }
      }
      return TRUE;
    });
  }

}
