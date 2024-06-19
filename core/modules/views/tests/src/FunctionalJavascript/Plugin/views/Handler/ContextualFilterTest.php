<?php

declare(strict_types=1);

namespace Drupal\Tests\views\FunctionalJavascript\Plugin\views\Handler;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the contextual filter handler UI.
 *
 * @group views
 */
class ContextualFilterTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'views',
    'views_ui',
    'views_test_config',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_field_body'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    ViewTestData::createTestViews(static::class, ['views_test_config']);

    // Always show advanced column.
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.show.advanced_column', TRUE)->save();

    // Disable automatic live preview to make the sequence of calls clearer. And
    // prevent errors on saving the view with the preview ajax load that are
    // cancelled.
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.always_live_preview', FALSE)->save();

    $account = $this->drupalCreateUser(['administer views']);
    $this->drupalLogin($account);
  }

  /**
   * Tests adding a contextual filter handler through the UI.
   */
  public function testAddContextualFilterUI(): void {
    $this->drupalGet('/admin/structure/views/view/test_field_body');

    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $page->clickLink('views-add-argument');

    $field = $web_assert->waitForField('name[node_field_data.nid]');
    $this->assertNotEmpty($field);
    $field->check();

    $add_button = $page->find('css', '.ui-dialog-buttonset .button--primary');
    $add_button->click();

    $field_action = $web_assert->waitForField('options[default_action]');
    $this->assertNotEmpty($field_action);
    $field_action->setValue('default');

    $page->selectFieldOption('options[default_argument_type]', 'node');
    $add_button = $page->find('css', '.ui-dialog-buttonset .button--primary');
    $add_button->click();

    // Wait for the dialog to close.
    $page->waitFor(10, function () use ($page) {
      $field = $page->find('css', '.ui-dialog-buttonset .button--primary');
      return empty($field);
    });

    $page->pressButton('edit-actions-submit');

    $page->clickLink('Content: ID');
    // Check that the dialog opens.
    $field_action = $web_assert->waitForField('options[default_action]');
    $this->assertNotEmpty($field_action);
  }

}
