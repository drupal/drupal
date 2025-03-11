<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Kernel;

use Drupal\Core\Config\Action\ConfigActionManager;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\MediaType;

/**
 * @group media
 */
class ConfigActionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media'];

  /**
   * The configuration action manager.
   */
  private readonly ConfigActionManager $configActionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get(ModuleInstallerInterface::class)->install([
      'media_test_type',
    ]);
    $this->configActionManager = $this->container->get('plugin.manager.config_action');
  }

  /**
   * Tests the application of configuration actions on a media type.
   */
  public function testConfigActions(): void {
    $media_type = MediaType::load('test');
    $this->assertSame('Test type.', $media_type->getDescription());
    $this->assertSame(['metadata_attribute' => 'field_attribute_config_test'], $media_type->getFieldMap());

    $this->configActionManager->applyAction(
      'entity_method:media.type:setDescription',
      $media_type->getConfigDependencyName(),
      'Changed by a config action...',
    );
    $this->configActionManager->applyAction(
      'entity_method:media.type:setFieldMap',
      $media_type->getConfigDependencyName(),
      ['foo' => 'baz'],
    );

    $media_type = MediaType::load('test');
    $this->assertSame('Changed by a config action...', $media_type->getDescription());
    $this->assertSame(['foo' => 'baz'], $media_type->getFieldMap());
  }

}
