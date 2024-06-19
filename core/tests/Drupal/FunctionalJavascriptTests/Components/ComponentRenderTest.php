<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Components;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the correct rendering of components.
 *
 * @group sdc
 */
class ComponentRenderTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'sdc_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'sdc_theme_test';

  /**
   * Tests that the correct libraries are put on the page using CSS.
   *
   * This also covers all the path translations necessary to produce the correct
   * path to the assets.
   */
  public function testCssLibraryAttachesCorrectly(): void {
    $build = [
      '#type' => 'inline_template',
      '#template' => "{{ include('sdc_theme_test:lib-overrides') }}",
    ];
    \Drupal::state()->set('sdc_test_component', $build);
    $this->drupalGet('sdc-test-component');
    $wrapper = $this->getSession()->getPage()->find('css', '#sdc-wrapper');
    // Opacity is set to 0 in the CSS file (see another-stylesheet.css).
    $this->assertFalse($wrapper->isVisible());
  }

  /**
   * Tests that the correct libraries are put on the page using JS.
   *
   * This also covers all the path translations necessary to produce the correct
   * path to the assets.
   */
  public function testJsLibraryAttachesCorrectly(): void {
    $build = [
      '#type' => 'inline_template',
      '#template' => "{{ include('sdc_test:my-button', {
        text: 'Click'
      }, with_context = false) }}",
    ];
    \Drupal::state()->set('sdc_test_component', $build);
    $this->drupalGet('sdc-test-component');
    $page = $this->getSession()->getPage();
    $page->find('css', '[data-component-id="sdc_test:my-button"]')
      ->click();
    $this->assertSame(
      'Click power (1)',
      $page->find('css', '[data-component-id="sdc_test:my-button"]')->getText(),
    );
  }

}
