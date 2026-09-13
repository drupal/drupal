<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Theme\DesignToken;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\DesignToken\DesignTokenValueInterface;
use Drupal\Core\Theme\DesignToken\DesignTokenValuePluginManager;
use Drupal\Core\Theme\DesignToken\DesignTokenValuePluginManagerInterface;
use Drupal\Core\Theme\Plugin\DesignTokenValue\Dimension;
use Drupal\Core\Theme\Plugin\DesignTokenValue\FontFamily;
use Drupal\Core\Theme\Plugin\DesignTokenValue\FontWeight;
use Drupal\Core\Theme\Plugin\DesignTokenValue\Number;
use Drupal\Core\Theme\Plugin\DesignTokenValue\Typography;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the design token value plugin manager.
 */
#[CoversClass(DesignTokenValuePluginManager::class)]
#[Group('design_token')]
#[RunTestsInSeparateProcesses]
class DesignTokenValuePluginManagerKernelTest extends KernelTestBase {

  /**
   * The DesignTokenValuePluginManager service.
   */
  protected DesignTokenValuePluginManagerInterface $designTokenValuePluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->designTokenValuePluginManager = $this->container->get(DesignTokenValuePluginManagerInterface::class);
  }

  /**
   * Tests detected plugins provided by Core.
   */
  public function testDetectedPlugins(): void {
    $definitions = $this->designTokenValuePluginManager->getDefinitions();

    $expected = [
      'dimension' => [
        'class' => Dimension::class,
        'provider' => 'core',
        'id' => 'dimension',
        'label' => new TranslatableMarkup('Dimension'),
      ],
      'fontFamily' => [
        'class' => FontFamily::class,
        'provider' => 'core',
        'id' => 'fontFamily',
        'label' => new TranslatableMarkup('Font family'),
      ],
      'fontWeight' => [
        'class' => FontWeight::class,
        'provider' => 'core',
        'id' => 'fontWeight',
        'label' => new TranslatableMarkup('Font weight'),
      ],
      'number' => [
        'class' => Number::class,
        'provider' => 'core',
        'id' => 'number',
        'label' => new TranslatableMarkup('Number'),
      ],
      'typography' => [
        'class' => Typography::class,
        'provider' => 'core',
        'id' => 'typography',
        'label' => new TranslatableMarkup('Typography'),
      ],
    ];

    foreach ($expected as $id => $infos) {
      $this->assertArrayHasKey($id, $definitions);
      foreach ($infos as $key => $expectedValue) {
        $this->assertEquals($expectedValue, $definitions[$id][$key]);
      }
    }
  }

  /**
   * Tests plugin interfaces.
   */
  public function testPluginInterfaces(): void {
    $configuration = [
      'unit' => 'rem',
      'value' => 2,
    ];
    /** @var \Drupal\Core\Theme\Plugin\DesignTokenValue\Dimension $instance */
    $instance = $this->designTokenValuePluginManager->createInstance('dimension', $configuration);
    $this->assertInstanceOf(Dimension::class, $instance);
    $this->assertInstanceOf(DesignTokenValueInterface::class, $instance);
  }

}
