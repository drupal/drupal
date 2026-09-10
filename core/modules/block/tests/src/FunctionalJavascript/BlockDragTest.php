<?php

declare(strict_types=1);

namespace Drupal\Tests\block\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests drag and drop blocks on block layout page.
 */
#[Group('block')]
#[RunTestsInSeparateProcesses]
class BlockDragTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'block', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([
      'administer blocks',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests drag and drop blocks.
   */
  public function testDragAndDropBlocks(): void {
    // Resize window to work around https://github.com/bitovi/syn/issues/164.
    $this->getSession()->resizeWindow(1024, 2048);
    $this->drupalGet('admin/structure/block');
    $assertSession = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();

    // Test if drag orientation on block layout page was applied with success.
    $this->assertNotEmpty($assertSession->waitForElementVisible('css', '.tabledrag-handle-y'));

    // Dragging Secondary tabs and Status messages to header region.
    $primaryTabs = $this->getDragRow($page, 'edit-blocks-claro-primary-local-tasks');

    $secondaryTabs = $this->getDragRow($page, 'edit-blocks-claro-secondary-local-tasks');
    $secondaryTabs->dragTo($primaryTabs);
    $messages = $this->getDragRow($page, 'edit-blocks-claro-messages');
    $messages->dragTo($primaryTabs);

    // Test if both blocks above were positioned on the header region.
    $this->assertEquals(
      'header',
      $page->findField('edit-blocks-claro-secondary-local-tasks-region')->getValue(),
      'Main menu should be positioned on header region'
    );
    $this->assertEquals(
      'header',
      $page->findField('edit-blocks-claro-messages-region')->getValue(),
      'Status messages should be positioned on header region'
    );

    // Check if the message unsaved changed appears.
    $assertSession->pageTextContains('You have unsaved changes.');

    // Test if the message for empty regions appear after drag the unique block
    // on the region.
    $noBlockMessage = $page->find('css', 'tr[data-drupal-selector="edit-blocks-region-pre-content-message"] td')->getText();
    $this->assertSession()->assert($noBlockMessage === 'No blocks in this region', 'Region pre-content should be empty.');

    // Testing drag row to an empty region.
    $pageTitle = $this->getDragRow($page, 'edit-blocks-claro-page-title');
    $heroRegion = $page->find('css', 'tr[data-drupal-selector="edit-blocks-region-help-message"]');
    $pageTitle->dragTo($heroRegion);
    $this->assertSession()->assert(
      $page->find('css', 'tr[data-drupal-selector="edit-blocks-region-help-message"] td')->getText() !== 'No blocks in this region',
      "Region help shouldn't be empty"
    );

  }

  /**
   * Helper function to find block tr element on the page.
   */
  private function getDragRow($page, string $blockId) {
    return $page->find('css', '#blocks tbody tr[data-drupal-selector="' . $blockId . '"] a.tabledrag-handle');
  }

}
