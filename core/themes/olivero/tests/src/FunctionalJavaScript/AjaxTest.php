<?php

declare(strict_types=1);

namespace Drupal\Tests\olivero\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests AJAX responses.
 */
#[Group('Ajax')]
#[RunTestsInSeparateProcesses]
class AjaxTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ajax_test', 'ajax_forms_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'olivero';

  /**
   * Tests that Ajax errors are visible in the UI.
   */
  public function testUiAjaxException(): void {
    $this->drupalGet('ajax-test/exception-link');
    $page = $this->getSession()->getPage();
    // We don't want the test to error out because of an expected Javascript
    // console error.
    $this->failOnJavascriptConsoleErrors = FALSE;
    // Click on the AJAX link.
    $this->clickLink('Ajax Exception');
    $this->assertSession()
      ->statusMessageContainsAfterWait("Oops, something went wrong. Check your browser's developer console for more details.", 'error');

    // Check that the message can be closed.
    $this->click('.messages__close');
    $this->assertTrue($page->find('css', '.messages--error')
      ->hasClass('hidden'));

    // This is needed to avoid an unfinished AJAX request error from tearDown()
    // because this test intentionally does not complete all AJAX requests.
    $this->getSession()->executeScript("delete window.drupalActiveXhrCount");
  }

}
