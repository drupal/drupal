<?php

namespace Drupal\Tests\views\FunctionalJavascript\Plugin\views\Handler;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the contextual filter handler UI.
 *
 * @group views
 */
class ContextualFilterTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'views', 'views_ui', 'views_test_config'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_field_body'];


  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), ['views_test_config']);

    // Disable automatic live preview to make the sequence of calls clearer.
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.show.advanced_column', TRUE)->save();

    $account = $this->drupalCreateUser(['administer views']);
    $this->drupalLogin($account);
  }

  /**
   * Test adding a contextual filter handler through the UI.
   */
  public function testAddContextualFilterUI() {
    $web_assert = $this->assertSession();

    $this->drupalGet('/admin/structure/views/view/test_field_body');
    $web_assert->assertWaitOnAjaxRequest();

    $page = $this->getSession()->getPage();

    $page->clickLink('views-add-argument');
    $web_assert->assertWaitOnAjaxRequest();

    $page->checkField('name[node_field_data.nid]');
    $add_button = $page->find('css', '.ui-dialog-buttonset .button--primary');
    $add_button->click();
    $web_assert->assertWaitOnAjaxRequest();

    $page->fillField('options[default_action]', 'default');
    $page->selectFieldOption('options[default_argument_type]', 'node');
    $add_button = $page->find('css', '.ui-dialog-buttonset .button--primary');
    $add_button->click();
    $web_assert->assertWaitOnAjaxRequest();
    $page->pressButton('edit-actions-submit');
    $web_assert->assertWaitOnAjaxRequest();
    $page->clickLink('Content: ID');
    // Check that the dialog opens.
    $web_assert->assertWaitOnAjaxRequest();
    $page->pressButton('Close');
  }

}
