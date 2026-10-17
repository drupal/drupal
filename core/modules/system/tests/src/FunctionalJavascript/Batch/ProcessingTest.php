<?php

declare(strict_types=1);

namespace Drupal\Tests\system\FunctionalJavascript\Batch;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Processing.
 */
#[Group('Batch')]
#[RunTestsInSeparateProcesses]
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

  /**
   * Tests that JS batches replace the op in the URL and do not add to it.
   */
  public function testBatchUrls(): void {
    $edit = ['batch' => 'batch9'];
    $this->drupalGet('batch-test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->waitForElementVisible('css', '.batch-urls');
    $batch_url_list = $this->assertSession()->elementExists('css', '.batch-urls');
    $batch_url_list_items = $batch_url_list->findAll('css', 'li');
    $this->assertCount(5, $batch_url_list_items);
    foreach ($batch_url_list_items as $batch_url_list_item) {
      $this->assertStringEndsWith('/batch?_format=json&id=1&op=do', $batch_url_list_item->getText());
    }
    $batch_finished_url = $this->assertSession()->elementExists('css', '.batch-finished-url');
    $this->assertStringEndsWith('/batch?id=1&op=finished', $batch_finished_url->getText());
  }

}
