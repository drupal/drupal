<?php

declare(strict_types=1);

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Image\ImageFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validator for the FileIsImageConstraint.
 */
class FileIsImageConstraintValidator extends BaseFileConstraintValidator implements ContainerInjectionInterface {

  /**
   * Creates a new FileIsImageConstraintValidator.
   *
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   The image factory.
   */
  public function __construct(
    protected ImageFactory $imageFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('image.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint) {
    $file = $this->assertValueIsFile($value);
    if (!$constraint instanceof FileIsImageConstraint) {
      throw new UnexpectedTypeException($constraint, FileIsImageConstraint::class);
    }

    $image = $this->imageFactory->get($file->getFileUri());
    if (!$image->isValid()) {
      $supportedExtensions = $this->imageFactory->getSupportedExtensions();
      $this->context->addViolation($constraint->message, ['%types' => implode(', ', $supportedExtensions)]);
    }
  }

}
