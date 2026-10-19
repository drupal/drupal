<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests editing a field's aggregation function via AJAX.
 *
 * @see https://www.drupal.org/project/drupal/issues/3344910
 */
#[Group('views_ui')]
#[RunTestsInSeparateProcesses]
class AggregationAjaxTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views', 'views_ui', 'views_test_config'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The views used for testing.
   *
   * @var array
   */
  public static $testViews = ['test_content_ajax'];

  /**
   * The message Drupal shows on the page for an unhandled AJAX error.
   */
  protected const AJAX_ERROR_MESSAGE = "Oops, something went wrong. Check your browser's developer console for more details.";

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Disable automatic live preview to make the sequence of calls clearer.
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.always_live_preview', FALSE)->save();

    ViewTestData::createTestViews(self::class, ['views_test_config']);

    $admin_user = $this->drupalCreateUser(['administer views']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests switching a field's aggregation function via the AJAX dialog.
   */
  public function testSwitchAggregationFunctionViaAjax(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Add an isolated runtime display with a single field.
    $view_name = static::$testViews[0];
    $view = Views::getView($view_name);
    $display_id = $view->storage->addDisplay('page');
    $display = &$view->storage->getDisplay($display_id);
    $display['display_options']['defaults']['fields'] = FALSE;
    $display['display_options']['fields'] = [
      'changed' => [
        'id' => 'changed',
        'table' => 'node_field_data',
        'field' => 'changed',
        'relationship' => 'none',
        'group_type' => 'group',
        'plugin_id' => 'field',
        'entity_type' => 'node',
        'entity_field' => 'changed',
      ],
    ];
    $view->save();

    $this->drupalGet('admin/structure/views/view/' . $view_name . '/edit/' . $display_id);

    // Enable aggregation for the display.
    $this->openDisplayOptionDialog('group_by');
    $page->checkField('group_by');
    $this->pressDialogButton('Apply');
    $this->assertNotEmpty(
      $assert_session->waitForElementRemoved('css', '.views-ui-dialog'),
      'The "Use aggregation" dialog is still open after applying the change.'
    );

    // Configure the field's aggregation function for the first time.
    $this->setFieldAggregationFunction('count');

    // Re-open the same, already-configured handler and switch the
    // aggregation function from COUNT to COUNT DISTINCT.
    $this->setFieldAggregationFunction('count_distinct');

    // Verify that the link to the aggregation settings is still present.
    $assert_session->linkExists('Aggregation settings');
  }

  /**
   * Opens the AJAX dialog for a display-level "Advanced" setting.
   *
   * @param string $option_id
   *   The machine name of the display option to open, e.g. 'group_by'.
   */
  protected function openDisplayOptionDialog(string $option_id): void {
    $assert_session = $this->assertSession();

    $link = $assert_session->waitForElementVisible('css', sprintf('a[href$="/%s"]', $option_id));
    $this->assertNotEmpty($link, sprintf('The link for the "%s" display option was not found.', $option_id));
    $link->click();

    $this->assertNotEmpty(
      $assert_session->waitForElementVisible('css', '.views-ui-dialog'),
      sprintf('The dialog for the "%s" display option did not open.', $option_id)
    );
  }

  /**
   * Opens the aggregation settings dialog and applies an aggregation function to the 'changed' field.
   *
   * @param string $group_type
   *   The aggregation function to select, e.g. 'count' or 'count_distinct'.
   */
  protected function setFieldAggregationFunction(string $group_type): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Opens Aggregation settings dialog.
    $page->clickLink('Aggregation settings');
    $this->assertNotEmpty(
      $assert_session->waitForElementVisible('css', '.views-ui-dialog'),
      sprintf('The aggregation settings dialog did not open while setting "%s".', $group_type)
    );
    $assert_session->fieldExists('options[group_type]');
    $page->selectFieldOption('options[group_type]', $group_type);
    $this->pressDialogButton('Apply');

    // Check if the settings dialog closes.
    $this->assertNotEmpty(
      $assert_session->waitForElementRemoved('css', '.views-ui-dialog'),
      sprintf('The aggregation settings dialog is still open after setting "%s".', $group_type)
    );

    // Check for the AJAX error message.
    $assert_session->pageTextNotContains(static::AJAX_ERROR_MESSAGE);
  }

  /**
   * Presses a button inside the currently open Views UI modal dialog.
   *
   * @param string $text
   *   The visible text of the button to press.
   */
  protected function pressDialogButton(string $text): void {
    $this->assertSession()
      ->elementExists('css', sprintf('.ui-dialog-buttonset button:contains("%s")', $text))
      ->press();
  }

}
