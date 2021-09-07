<?php

namespace Drupal\FunctionalTests\Core\Render;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

/**
 * Tests deprecated render() function.
 *
 * @group Render
 * @group legacy
 */
class RenderDeprecationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['render_deprecation'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests deprecated render() function.
   */
  public function testRenderDeprecation(): void {
    $this->expectDeprecation('The render() function is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Render\RendererInterface::render() instead. See https://www.drupal.org/node/2939099');
    $id = '#render-deprecation-test-result';
    $this->drupalGet(Url::fromRoute('render_deprecation.function')->getInternalPath());
    /** @var \Behat\Mink\Element\NodeElement $function_render */
    $function_render = $this->getSession()->getPage()->find('css', $id);

    $this->drupalGet(Url::fromRoute('render_deprecation.service')->getInternalPath());
    /** @var \Behat\Mink\Element\NodeElement $service_render */
    $service_render = $this->getSession()->getPage()->find('css', $id);

    $this->assertEquals(
      $service_render->getOuterHtml(),
      $function_render->getOuterHtml()
    );
  }

}
