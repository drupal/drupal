<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Kernel;

use Drupal\Core\Config\Action\ConfigActionManager;
use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Config Actions.
 */
#[Group('image')]
#[RunTestsInSeparateProcesses]
class ConfigActionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['image', 'system'];

  /**
   * The configuration action manager.
   */
  private readonly ConfigActionManager $configActionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'image']);
    $this->configActionManager = $this->container->get('plugin.manager.config_action');
  }

  /**
   * Tests adding an image effect using the configuration action manager.
   */
  public function testConfigActions(): void {
    $style = ImageStyle::load('large');
    $this->assertCount(2, $style->getEffects());

    $this->configActionManager->applyAction(
      'entity_method:image.style:addImageEffect',
      $style->getConfigDependencyName(),
      ['id' => 'image_desaturate', 'weight' => 1],
    );

    $this->assertCount(3, ImageStyle::load('large')->getEffects());
  }

}
