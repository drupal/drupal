<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Kernel;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\MediaTypeInterface;
use Drupal\media_test_oembed\Hook\EntityBundleInfoAlter;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests media validation.
 */
#[Group('media')]
#[RunTestsInSeparateProcesses]
final class MediaSourceValidationTest extends KernelTestBase {

  use MediaTypeCreationTrait;

  /**
   * The media type used for testing.
   */
  protected MediaTypeInterface $mediaType;

  /**
   * The field name of the media type.
   */
  protected string $fieldName;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media',
    'media_test_oembed',
    'field',
    'image',
    'file',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installEntitySchema('media');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['media']);
    $this->mediaType = $this->createMediaType('oembed:video');
    $this->fieldName = $this->mediaType->getSource()
      ->getSourceFieldDefinition($this->mediaType)
      ->getName();
    $this->container->get(StateInterface::class)->set(EntityBundleInfoAlter::STATE_FLAG, [
      $this->mediaType->id() => [$this->fieldName],
    ]);
    $this->container->get(EntityFieldManagerInterface::class)->clearCachedFieldDefinitions();
  }

  /**
   * Gets mock client.
   *
   * @param array $requestHistory
   *   History container.
   * @param \GuzzleHttp\Psr7\Response|\Exception ...$responses
   *   Responses.
   *
   * @return \GuzzleHttp\ClientInterface
   *   Mock client
   */
  protected function mockClient(array &$requestHistory, Response|\Exception ...$responses): ClientInterface {
    $mock = new MockHandler(\array_values($responses));

    $handler_stack = HandlerStack::create($mock);
    $history = Middleware::history($requestHistory);
    $handler_stack->push($history);
    return new Client(['handler' => $handler_stack]);
  }

  /**
   * Tests existing validation constraints are respected by Media::validate.
   *
   * @legacy-covers \Drupal\media\Entity\Media::validate
   */
  public function testValidation(): void {
    $history = [];
    $this->container->set('http_client', $this->mockClient(
      $history,
      new Response(body: \file_get_contents(dirname(__DIR__, 2) . '/fixtures/oembed/providers.json')),
      new Response(body: \file_get_contents(dirname(__DIR__, 2) . '/fixtures/oembed/video_youtube.json')),
      new Response(body: \file_get_contents(dirname(__DIR__, 2) . '/fixtures/oembed/video_youtube.json')),
    ));
    // Add an allowed video.
    $media = Media::create([
      'bundle' => $this->mediaType->id(),
      $this->fieldName => 'https://www.youtube.com/watch?v=15Nqbic6HZs',
      'name' => $this->randomMachineName(),
    ]);
    self::assertCount(0, $media->validate());
    // Save this item so we can test the UniqueField constraint later.
    $media->save();

    // Add a disallowed video.
    $media = Media::create([
      'bundle' => $this->mediaType->id(),
      $this->fieldName => 'https://www.youtube.com/watch?v=9qbRHY1l0vc',
      'name' => $this->randomMachineName(),
    ]);
    $violations = $media->validate();
    self::assertCount(1, $violations);
    self::assertEquals($this->fieldName . '.0.value', $violations->get(0)->getPropertyPath());
    self::assertEquals('This site only allows Jazz videos, try again cat 🎷', (string) $violations->get(0)->getMessage());

    // Add an allowed video with an existing URL.
    // This should trigger the UniqueField constraint.
    $media = Media::create([
      'bundle' => $this->mediaType->id(),
      $this->fieldName => 'https://www.youtube.com/watch?v=15Nqbic6HZs',
      'name' => $this->randomMachineName(),
    ]);
    $violations = $media->validate();
    self::assertCount(1, $violations);
    self::assertEquals($this->fieldName, $violations->get(0)->getPropertyPath());
    self::assertEquals('A media item with Remote video URL <em class="placeholder">https://www.youtube.com/watch?v=15Nqbic6HZs</em> already exists.', (string) $violations->get(0)->getMessage());
  }

}
