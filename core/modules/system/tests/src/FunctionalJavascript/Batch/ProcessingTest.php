<?php

declare(strict_types=1);

namespace Drupal\Tests\system\FunctionalJavascript\Batch;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * @group Batch
 */
class ProcessingTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['batch_test', 'test_page_test'];

  /**
   * {@inheritdoc}
   *
   * @todo Use the stark theme in https://drupal.org/i/3407067.
   */
  protected $defaultTheme = 'olivero';

  /**
   * Tests that a link to the error page is shown.
   */
  public function testLinkToErrorPageAppears(): void {
    $edit = ['batch' => 'batch8'];
    $this->drupalGet('batch-test');
    $this->submitForm($edit, 'Submit');
    $this->assertNotNull($this->assertSession()->waitForLink('the error page'));
    $this->assertSession()->assertNoEscaped('<');
    $this->assertSession()->responseContains('Exception in batch');
    $this->clickLink('the error page');
    $this->assertSession()->pageTextContains('Redirection successful.');
  }

}
