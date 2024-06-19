<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the UI for entity displays.
 *
 * @group field_ui
 */
class EntityDisplayTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field_ui', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'administer entity_test display',
    ]));
  }

  /**
   * Tests the use of regions for entity view displays.
   */
  public function testEntityView(): void {
    $this->drupalGet('entity_test/structure/entity_test/display');
    $this->assertSession()->elementExists('css', '.region-content-message.region-empty');
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'hidden')->isSelected());

    $this->getSession()->getPage()->selectFieldOption('fields[field_test_text][region]', 'content');
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'content')->isSelected());

    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'content')->isSelected());
  }

}
