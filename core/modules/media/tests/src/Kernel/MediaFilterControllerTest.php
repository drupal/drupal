<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Kernel;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\filter\Entity\FilterFormat;
use Drupal\media\Controller\MediaFilterController;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests that media preview route follows the media's access control.
 */
#[CoversClass(MediaFilterController::class)]
#[Group('media')]
#[RunTestsInSeparateProcesses]
class MediaFilterControllerTest extends MediaEmbedFilterTestBase {

  /**
   * The text format used in the preview request.
   */
  protected FilterFormat $filterFormat;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(['user']);

    $this->filterFormat = FilterFormat::create([
      'format' => 'media_preview_test',
      'name' => 'Media preview test',
      'roles' => [RoleInterface::ANONYMOUS_ID],
      'filters' => [
        'media_embed' => ['status' => TRUE],
      ],
    ]);
    $this->filterFormat->save();
    Role::load(RoleInterface::ANONYMOUS_ID)->grantPermission('use text format media_preview_test')->save();
    $this->container->get('current_user')->setAccount(new AnonymousUserSession());
  }

  /**
   * Tests that media access is validated on media filter preview request.
   */
  public function testPreview(): void {
    $text = $this->createEmbedCode([
      'data-entity-type' => 'media',
      'data-entity-uuid' => static::EMBEDDED_ENTITY_UUID,
    ]);

    $url = Url::fromRoute('media.filter.preview', [
      'filter_format' => $this->filterFormat->id(),
    ], [
      'query' => [
        'text' => $text,
        'uuid' => static::EMBEDDED_ENTITY_UUID,
      ],
    ]);
    $output = $this->drupalGet(
      $url,
      [],
      ['X-Drupal-MediaPreview-CSRF-Token' => 'placeholder-token'],
    );

    $session = $this->getSession();
    $this->assertSame(Response::HTTP_OK, $session->getStatusCode());
    $this->assertStringNotContainsString('<drupal-media', $output);
    $media = \Drupal::service('entity.repository')->loadEntityByUuid('media', static::EMBEDDED_ENTITY_UUID);
    $this->assertFalse($media->access('view label', \Drupal::currentUser()));
    self::assertSame(
      \sprintf('Media %s', $media->id()),
      $session->getResponseHeader('Drupal-Media-Label'),
    );
  }

}
