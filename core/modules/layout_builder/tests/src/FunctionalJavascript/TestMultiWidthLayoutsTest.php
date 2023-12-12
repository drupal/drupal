<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;

/**
 * Test the multi-width layout plugins.
 *
 * @group layout_builder
 */
class TestMultiWidthLayoutsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'block',
    'node',
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

    $this->createContentType(['type' => 'bundle_with_section_field']);
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $this->createNode([
      'type' => 'bundle_with_section_field',
    ]);
    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
    ]));
  }

  /**
   * Tests changing the columns widths of a multi-width section.
   */
  public function testWidthChange() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/1/layout');
    $width_options = [
      [
        'label' => 'Two column',
        'default_width' => '50-50',
        'additional_widths' => [
          '33-67',
          '67-33',
          '25-75',
          '75-25',
        ],
        'class' => 'layout--twocol-section--',
      ],
      [
        'label' => 'Three column',
        'default_width' => '33-34-33',
        'additional_widths' => [
          '25-50-25',
          '25-25-50',
          '50-25-25',
        ],
        'class' => 'layout--threecol-section--',
      ],
    ];
    foreach ($width_options as $width_option) {
      $width = $width_option['default_width'];
      $assert_session->linkExists('Add section');
      $page->clickLink('Add section');
      $this->assertNotEmpty($assert_session->waitForElementVisible('css', "#drupal-off-canvas a:contains(\"{$width_option['label']}\")"));
      $page->clickLink($width_option['label']);
      $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas input[type="submit"][value="Add section"]'));
      $page->pressButton("Add section");
      $this->assertWidthClassApplied($width_option['class'] . $width);
      foreach ($width_option['additional_widths'] as $width) {
        $width_class = $width_option['class'] . $width;
        $assert_session->linkExists('Configure Section 1');
        $page->clickLink('Configure Section 1');
        $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas input[type="submit"][value="Update"]'));
        $page->findField('layout_settings[column_widths]')->setValue($width);
        $page->pressButton("Update");
        $this->assertWidthClassApplied($width_class);
      }
      $assert_session->linkExists('Remove Section 1');
      $this->clickLink('Remove Section 1');
      $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas input[type="submit"][value="Remove"]'));
      $page->pressButton('Remove');
      $assert_session->assertNoElementAfterWait('css', ".$width_class");
    }
  }

  /**
   * Asserts the width class is applied to the first section.
   *
   * @param string $width_class
   *   The width class.
   *
   * @internal
   */
  protected function assertWidthClassApplied(string $width_class): void {
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', ".{$width_class}[data-layout-delta=\"0\"]"));
  }

}
