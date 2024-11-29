<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Icon;

use Drupal\Core\Template\IconsTwigExtension;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Core\Template\IconsTwigExtension
 *
 * @group icon
 */
class IconsTwigExtensionTest extends TestCase {

  /**
   * The twig extension.
   *
   * @var \Drupal\Core\Template\IconsTwigExtension
   */
  private IconsTwigExtension $iconsTwigExtension;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->iconsTwigExtension = new IconsTwigExtension();
  }

  /**
   * Test the IconsTwigExtension::getFunctions method.
   */
  public function testGetFunctions(): void {
    $functions = $this->iconsTwigExtension->getFunctions();
    $this->assertCount(1, $functions);
    $this->assertEquals('icon', $functions[0]->getName());
  }

  /**
   * Test the IconsTwigExtension::getIconRenderable method.
   */
  public function testGetIconRenderable(): void {
    $settings = ['foo' => 'bar'];
    $result = $this->iconsTwigExtension->getIconRenderable('pack_id', 'icon_id', $settings);
    $expected = [
      '#type' => 'icon',
      '#pack_id' => 'pack_id',
      '#icon_id' => 'icon_id',
      '#settings' => $settings,
    ];
    $this->assertEquals($expected, $result);
  }

}
