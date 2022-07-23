<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\OEmbed\Provider;
use Drupal\media\OEmbed\ResourceFetcher;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\Plugin\Validation\Constraint\OEmbedResourceConstraint;
use Drupal\media\Plugin\Validation\Constraint\OEmbedResourceConstraintValidator;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Prophecy\Argument;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @coversDefaultClass \Drupal\media\Plugin\Validation\Constraint\OEmbedResourceConstraintValidator
 *
 * @group media
 */
class OEmbedResourceConstraintValidatorTest extends KernelTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'file', 'image', 'media', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
  }

  /**
   * @covers ::validate
   */
  public function testValidateEmptySource() {
    $media = Media::create([
      'bundle' => $this->createMediaType('oembed:video')->id(),
    ]);

    $constraint = new OEmbedResourceConstraint();

    // The media item has an empty source value, so the constraint validator
    // should add a violation and return early before invoking the URL resolver.
    $context = $this->prophesize(ExecutionContextInterface::class);
    $context->addViolation($constraint->invalidResourceMessage)->shouldBeCalled();

    $url_resolver = $this->prophesize(UrlResolverInterface::class);
    $url_resolver->getProviderByUrl(Argument::any())->shouldNotBeCalled();

    $validator = new OEmbedResourceConstraintValidator(
      $url_resolver->reveal(),
      $this->container->get('media.oembed.resource_fetcher'),
      $this->container->get('logger.factory')
    );
    $validator->initialize($context->reveal());
    $validator->validate($this->getValue($media), $constraint);
  }

  /**
   * @covers ::validate
   */
  public function testValidateUrlResolverInvoked() {
    $media = Media::create([
      'bundle' => $this->createMediaType('oembed:video')->id(),
      'field_media_oembed_video' => 'source value',
    ]);

    $constraint = new OEmbedResourceConstraint();

    $context = $this->prophesize(ExecutionContextInterface::class);

    $provider = $this->prophesize(Provider::class);
    $provider->getName()->willReturn('YouTube');

    $url_resolver = $this->prophesize(UrlResolverInterface::class);
    $url_resolver->getProviderByUrl(Argument::any())->willReturn($provider->reveal());
    $url_resolver->getResourceUrl(Argument::any())->shouldBeCalledOnce();

    $validator = new OEmbedResourceConstraintValidator(
      $url_resolver->reveal(),
      $this->prophesize(ResourceFetcher::class)->reveal(),
      $this->container->get('logger.factory')
    );
    $validator->initialize($context->reveal());
    $validator->validate($this->getValue($media), $constraint);
  }

  /**
   * Wraps a media entity in an anonymous class to mock a field value.
   *
   * @param \Drupal\media\Entity\Media $media
   *   The media object.
   *
   * @return object
   *   The mock field value to validate.
   */
  protected function getValue(Media $media) {
    return new class ($media) {

      private $entity;

      public function __construct($entity) {
        $this->entity = $entity;
      }

      public function getEntity() {
        return $this->entity;
      }

    };
  }

}
