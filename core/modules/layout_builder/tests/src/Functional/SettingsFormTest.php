<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Layout Builder settings form.
 *
 * @coversDefaultClass \Drupal\layout_builder\Form\LayoutBuilderSettingsForm
 * @group layout_builder
 */
class SettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['layout_builder'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the Layout Builder settings form.
   */
  public function testSettingsForm(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer layout builder',
    ]));

    $this->drupalGet(Url::fromRoute('layout_builder.settings'));
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([
      'expose_all_field_blocks' => 1,
    ], 'Save configuration');

    $this->assertSession()->pageTextContains('The configuration options have been saved');
    $this->assertSession()->checkboxChecked('expose_all_field_blocks');
    $this->submitForm([
      'expose_all_field_blocks' => 0,
    ], 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved');
    $this->assertSession()->checkboxNotChecked('expose_all_field_blocks');
  }

}
