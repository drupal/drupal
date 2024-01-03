<?php

declare(strict_types=1);

namespace Drupal\Tests\big_pipe\FunctionalJavascript;

use Drupal\big_pipe\Render\BigPipe;
use Drupal\big_pipe_regression_test\BigPipeRegressionTestController;
use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * BigPipe regression tests.
 *
 * @group big_pipe
 */
class BigPipeRegressionTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'big_pipe',
    'big_pipe_regression_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Use the big_pipe_test_theme theme.
    $this->container->get('theme_installer')->install(['big_pipe_test_theme']);
    $this->container->get('config.factory')->getEditable('system.theme')->set('default', 'big_pipe_test_theme')->save();
  }

  /**
   * Ensure BigPipe works despite inline JS containing the string "</body>".
   *
   * @see https://www.drupal.org/node/2678662
   */
  public function testMultipleClosingBodies_2678662() {
    $this->assertTrue($this->container->get('module_installer')->install(['render_placeholder_message_test'], TRUE), 'Installed modules.');

    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalGet(Url::fromRoute('big_pipe_regression_test.2678662'));

    // Confirm that AJAX behaviors were instantiated, if not, this points to a
    // JavaScript syntax error.
    $javascript = <<<JS
    (function(){
      return Object.keys(Drupal.ajax.instances).length > 0;
    }())
JS;
    $this->assertJsCondition($javascript);

    // Besides verifying there is no JavaScript syntax error, also verify the
    // HTML structure.
    // The BigPipe stop signal is present just before the closing </body> and
    // </html> tags.
    $this->assertSession()
      ->responseContains(BigPipe::STOP_SIGNAL . "\n\n\n</body></html>");
    $js_code_until_closing_body_tag = substr(BigPipeRegressionTestController::MARKER_2678662, 0, strpos(BigPipeRegressionTestController::MARKER_2678662, '</body>'));
    // The BigPipe start signal does NOT start at the closing </body> tag string
    // in an inline script.
    $this->assertSession()
      ->responseNotContains($js_code_until_closing_body_tag . "\n" . BigPipe::START_SIGNAL);
  }

  /**
   * Ensure messages set in placeholders always appear.
   *
   * @see https://www.drupal.org/node/2712935
   */
  public function testMessages_2712935() {
    $this->assertTrue($this->container->get('module_installer')->install(['render_placeholder_message_test'], TRUE), 'Installed modules.');

    $this->drupalLogin($this->drupalCreateUser());
    $messages_markup = '<div role="contentinfo" aria-label="Status message"';

    $test_routes = [
      // Messages placeholder rendered first.
      'render_placeholder_message_test.first',
      // Messages placeholder rendered after one, before another.
      'render_placeholder_message_test.middle',
      // Messages placeholder rendered last.
      'render_placeholder_message_test.last',
    ];

    $assert = $this->assertSession();
    foreach ($test_routes as $route) {
      // Verify that we start off with zero messages queued.
      $this->drupalGet(Url::fromRoute('render_placeholder_message_test.queued'));
      $assert->responseNotContains($messages_markup);

      // Verify the test case at this route behaves as expected.
      $this->drupalGet(Url::fromRoute($route));
      $assert->elementContains('css', 'p.logged-message:nth-of-type(1)', 'Message: P1');
      $assert->elementContains('css', 'p.logged-message:nth-of-type(2)', 'Message: P2');
      $assert->responseContains($messages_markup);
      $assert->elementExists('css', 'div[aria-label="Status message"] ul');
      $assert->elementContains('css', 'div[aria-label="Status message"] ul li:nth-of-type(1)', 'P1');
      $assert->elementContains('css', 'div[aria-label="Status message"] ul li:nth-of-type(2)', 'P2');

      // Verify that we end with all messages printed, hence again zero queued.
      $this->drupalGet(Url::fromRoute('render_placeholder_message_test.queued'));
      $assert->responseNotContains($messages_markup);
    }
  }

  /**
   * Ensure default BigPipe placeholder HTML cannot split paragraphs.
   *
   * @see https://www.drupal.org/node/2802923
   */
  public function testPlaceholderInParagraph_2802923() {
    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalGet(Url::fromRoute('big_pipe_regression_test.2802923'));

    $this->assertJsCondition('document.querySelectorAll(\'p\').length === 1');
  }

  /**
   * Tests BigPipe large content.
   *
   * Repeat loading of same page for two times, after second time the page is
   * cached and the bug consistently reproducible.
   */
  public function testBigPipeLargeContent() {
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    $assert_session = $this->assertSession();

    $this->drupalGet(Url::fromRoute('big_pipe_test_large_content'));
    $this->assertNotNull($assert_session->waitForElement('css', 'script[data-big-pipe-event="stop"]'));
    $this->assertCount(0, $this->getDrupalSettings()['bigPipePlaceholderIds']);
    $this->assertCount(2, $this->getSession()->getPage()->findAll('css', 'script[data-big-pipe-replacement-for-placeholder-with-id]'));
    $assert_session->elementExists('css', '#big-pipe-large-content');

    $this->drupalGet(Url::fromRoute('big_pipe_test_large_content'));
    $this->assertNotNull($assert_session->waitForElement('css', 'script[data-big-pipe-event="stop"]'));
    $this->assertCount(0, $this->getDrupalSettings()['bigPipePlaceholderIds']);
    $this->assertCount(2, $this->getSession()->getPage()->findAll('css', 'script[data-big-pipe-replacement-for-placeholder-with-id]'));
    $assert_session->elementExists('css', '#big-pipe-large-content');
  }

  /**
   * Test BigPipe replacement of multiple complex replacements.
   *
   * In some situations with either a large number of replacements or multiple
   * replacements involving complex operations, some replacements were not
   * completed. This is a simulation of such a situation by rendering a lot of
   * placeholders on a page.
   *
   * @see https://www.drupal.org/node/3390178
   */
  public function testMultipleReplacements(): void {
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);

    $assert_session = $this->assertSession();

    $this->drupalGet(Url::fromRoute('big_pipe_test_multiple_replacements'));
    $this->assertNotNull($assert_session->waitForElement('css', 'script[data-big-pipe-event="stop"]'));
    $this->assertCount(0, $this->getDrupalSettings()['bigPipePlaceholderIds']);
    $this->assertCount(0, $this->getSession()->getPage()->findAll('css', 'span[data-big-pipe-placeholder-id]'));
    $this->assertCount(BigPipeRegressionTestController::PLACEHOLDER_COUNT + 1, $this->getSession()->getPage()->findAll('css', 'script[data-big-pipe-replacement-for-placeholder-with-id]'));
  }

}
