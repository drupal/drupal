<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\rest\Entity\RestResourceConfig;
use Drupal\rest\RestResourceConfigInterface;

/**
 * @group content_moderation
 */
class ContentModerationStateResourceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['serialization', 'rest', 'content_moderation'];

  /**
   * @see \Drupal\content_moderation\Entity\ContentModerationState
   */
  public function testCreateContentModerationStateResource() {
    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage('The "entity:content_moderation_state" plugin does not exist.');
    RestResourceConfig::create([
      'id' => 'entity.content_moderation_state',
      'granularity' => RestResourceConfigInterface::RESOURCE_GRANULARITY,
      'configuration' => [
        'methods' => ['GET'],
        'formats' => ['json'],
        'authentication' => ['cookie'],
      ],
    ])
      ->enable()
      ->save();
  }

}
