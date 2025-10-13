<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\rest\Entity\RestResourceConfig;
use Drupal\rest\RestResourceConfigInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Content Moderation State Resource.
 */
#[Group('content_moderation')]
#[RunTestsInSeparateProcesses]
class ContentModerationStateResourceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['serialization', 'rest', 'content_moderation'];

  /**
   * @see \Drupal\content_moderation\Entity\ContentModerationState
   */
  public function testCreateContentModerationStateResource(): void {
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
