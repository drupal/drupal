<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Kernel;

use Drupal\media\Plugin\Filter\MediaEmbed;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that media embed disables certain integrations.
 */
#[CoversClass(MediaEmbed::class)]
#[Group('media')]
#[RunTestsInSeparateProcesses]
class MediaEmbedFilterDisabledIntegrationsTest extends MediaEmbedFilterTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'contextual',
    // @see media_test_embed_entity_view_alter()
    'media_test_embed',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('system');
    $this->container->get('current_user')
      ->getAccount()
      ->addRole($this->drupalCreateRole([
        'access contextual links',
      ]));
  }

  /**
   * Tests disabled integrations.
   *
   * @legacy-covers ::renderMedia
   * @legacy-covers ::disableContextualLinks
   */
  public function testDisabledIntegrations(): void {
    $text = $this->createEmbedCode([
      'data-entity-type' => 'media',
      'data-entity-uuid' => static::EMBEDDED_ENTITY_UUID,
    ]);

    $this->applyFilter($text);
    $this->assertCount(1, $this->cssSelect('div[data-media-embed-test-view-mode]'));
    $this->assertCount(0, $this->cssSelect('div[data-media-embed-test-view-mode].contextual-region'));
  }

}
