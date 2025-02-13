<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Batch;

use Drupal\batch_test\BatchTestHelper;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the content of the progress page.
 *
 * @group Batch
 */
class PageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['batch_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the batch API progress page uses the correct theme.
   */
  public function testBatchProgressPageTheme(): void {
    // Make sure that the page which starts the batch (an administrative page)
    // is using a different theme than would normally be used by the batch API.
    $this->container->get('theme_installer')->install(['claro', 'olivero']);
    $this->config('system.theme')
      ->set('default', 'olivero')
      ->set('admin', 'claro')
      ->save();

    // Log in as an administrator who can see the administrative theme.
    $admin_user = $this->drupalCreateUser(['view the administration theme']);
    $this->drupalLogin($admin_user);
    // Visit an administrative page that runs a test batch, and check that the
    // theme that was used during batch execution (which the batch callback
    // function saved as a variable) matches the theme used on the
    // administrative page.
    $this->drupalGet('admin/batch-test/test-theme');
    // The stack should contain the name of the theme used on the progress
    // page.
    $batch_test_helper = new BatchTestHelper();
    $this->assertEquals(['claro'], $batch_test_helper->stack(), 'A progressive batch correctly uses the theme of the page that started the batch.');
  }

  /**
   * Tests that the batch API progress page shows the title correctly.
   */
  public function testBatchProgressPageTitle(): void {
    // Visit an administrative page that runs a test batch, and check that the
    // title shown during batch execution (which the batch callback function
    // saved as a variable) matches the theme used on the administrative page.
    // Run initial step only first.
    $this->maximumMetaRefreshCount = 0;
    $this->drupalGet('batch-test/test-title');
    $this->assertSession()->pageTextContains('Batch Test');

    // Leave the batch process running.
    $this->maximumMetaRefreshCount = NULL;
    $this->drupalGet('batch-test/test-title');

    // The stack should contain the title shown on the progress page.
    $batch_test_helper = new BatchTestHelper();
    $this->assertEquals(['Batch Test'], $batch_test_helper->stack(), 'The batch title is shown on the batch page.');
    $this->assertSession()->pageTextContains('Redirection successful.');
  }

  /**
   * Tests that the progress messages are correct.
   */
  public function testBatchProgressMessages(): void {
    // Go to the initial step only.
    $this->maximumMetaRefreshCount = 0;
    $this->drupalGet('batch-test/test-title');
    // Check that the initial progress message appears correctly and is not
    // double escaped.
    $this->assertSession()->responseContains('<div class="progress__description">Initializing.<br />&nbsp;</div>');
    $this->assertSession()->responseNotContains('&amp;nbsp;');
    // Now also go to the next step.
    $this->maximumMetaRefreshCount = 1;
    $this->drupalGet('batch-test/test-title');
    // Check that the progress message for second step appears correctly.
    $this->assertSession()->responseContains('<div class="progress__description">Completed 1 of 1.</div>');
  }

}
