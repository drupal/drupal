<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Core\Htmx;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that dynamic forms powered by HTMX work as expected.
 */
#[Group('Htmx')]
#[RunTestsInSeparateProcesses]
class HtmxDynamicFormTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'test_htmx',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests HTMX form functionality.
   */
  public function testHtmxForm(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'access content',
    ]));
    // Check that the data is empty on load.
    $this->drupalGet('/htmx-test-attachments/form-builder-test/');
    $page = $this->getSession()->getPage();
    $this->assertTrue($this->assertSession()->optionExists('selected', '- None -')->isSelected());
    $this->assertSession()->pageTextNotContains('Data is');

    // Check that the selectors are populated when selecting a type.
    $page->selectFieldOption('type', 'a');
    $this->assertSession()->assertExpectedAjaxRequest(1);
    $this->assertNotNull($this->assertSession()->optionExists('selected', '1'));
    $page->selectFieldOption('type', 'b');
    $this->assertSession()->assertExpectedAjaxRequest(2);
    $this->assertNotNull($this->assertSession()->optionExists('selected', '4'));

    // Check that the data is updated when selecting an option in
    // the second select.
    $this->assertSession()->pageTextNotContains('Data is a:2');
    // Store the build id.
    $buildId = $page->find('css', 'input[name="form_build_id"]')->getValue();
    $page->selectFieldOption('type', 'a');
    $this->assertSession()->assertExpectedAjaxRequest(3);
    // Verify the build id is updated.
    $this->assertNotEquals($buildId, $page->find('css', 'input[name="form_build_id"]')->getValue());
    $page->selectFieldOption('selected', '2');
    $this->assertSession()->assertExpectedAjaxRequest(4);
    $this->assertSession()->pageTextContains('Data is a:2');

    // Check that the data is empty when selecting the "- None -" option in
    // the config name.
    $page->selectFieldOption('selected', '- None -');
    $this->assertSession()->assertExpectedAjaxRequest(5);
    $this->assertSession()->pageTextNotContains('Data is');

    // Check that the data is emptied again when selecting a config type.
    $page->selectFieldOption('type', 'b');
    $this->assertSession()->assertExpectedAjaxRequest(6);
    $this->assertSession()->pageTextNotContains('Data is');

    // Confirm that changing type with data selected does not produce an error.
    $page->selectFieldOption('type', 'a');
    $this->assertSession()->assertExpectedAjaxRequest(7);
    $page->selectFieldOption('selected', '3');
    $this->assertSession()->assertExpectedAjaxRequest(8);
    $page->selectFieldOption('type', 'b');
    $this->assertSession()->assertExpectedAjaxRequest(9);
    $error = $this->getSession()->getPage()->find('css', 'select.error');
    $this->assertNull($error);
  }

}
