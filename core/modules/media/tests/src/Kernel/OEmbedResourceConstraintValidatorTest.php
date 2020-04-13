<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
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
  public function testValidate() {
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

    $value = new class ($media) {

      public function __construct($entity) {
        $this->entity = $entity;
      }

      public function getEntity() {
        return $this->entity;
      }

    };

    $validator = new OEmbedResourceConstraintValidator(
      $url_resolver->reveal(),
      $this->container->get('media.oembed.resource_fetcher'),
      $this->container->get('logger.factory')
    );
    $validator->initialize($context->reveal());
    $validator->validate($value, $constraint);
  }

}
