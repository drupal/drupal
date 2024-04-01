<?php

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validator for the FileImageDimensionsConstraint.
 *
 * This validator will resize the image if exceeds the limits.
 */
class FileImageDimensionsConstraintValidator extends BaseFileConstraintValidator implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Creates a new FileImageDimensionsConstraintValidator.
   *
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   The image factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    protected ImageFactory $imageFactory,
    protected MessengerInterface $messenger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('image.factory'),
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    $file = $this->assertValueIsFile($value);
    if (!$constraint instanceof FileImageDimensionsConstraint) {
      throw new UnexpectedTypeException($constraint, FileImageDimensionsConstraint::class);
    }

    $image = $this->imageFactory->get($file->getFileUri());
    if (!$image->isValid()) {
      return;
    }

    $scaling = FALSE;
    $maxDimensions = $constraint->maxDimensions;
    if ($maxDimensions) {
      // Check that it is smaller than the given dimensions.
      [$width, $height] = explode('x', $maxDimensions);
      if ($image->getWidth() > $width || $image->getHeight() > $height) {
        // Try to resize the image to fit the dimensions.
        if ($image->scale($width, $height)) {
          $scaling = TRUE;
          $image->save();
          if (!empty($width) && !empty($height)) {
            $this->messenger->addStatus($this->t('The image was resized to fit within the maximum allowed dimensions of %dimensions pixels. The new dimensions of the resized image are %new_widthx%new_height pixels.',
              [
                '%dimensions' => $maxDimensions,
                '%new_width' => $image->getWidth(),
                '%new_height' => $image->getHeight(),
              ]));
          }
          elseif (empty($width)) {
            $this->messenger->addStatus($this->t('The image was resized to fit within the maximum allowed height of %height pixels. The new dimensions of the resized image are %new_widthx%new_height pixels.',
              [
                '%height' => $height,
                '%new_width' => $image->getWidth(),
                '%new_height' => $image->getHeight(),
              ]));
          }
          elseif (empty($height)) {
            $this->messenger->addStatus($this->t('The image was resized to fit within the maximum allowed width of %width pixels. The new dimensions of the resized image are %new_widthx%new_height pixels.',
              [
                '%width' => $width,
                '%new_width' => $image->getWidth(),
                '%new_height' => $image->getHeight(),
              ]));
          }
        }
        else {
          $this->context->addViolation($constraint->messageResizeFailed);
        }
      }
    }

    $minDimensions = $constraint->minDimensions;
    if ($minDimensions) {
      // Check that it is larger than the given dimensions.
      [$width, $height] = explode('x', $minDimensions);
      if ($image->getWidth() < $width || $image->getHeight() < $height) {
        if ($scaling) {
          $this->context->addViolation($constraint->messageResizedImageTooSmall,
            [
              '%dimensions' => $minDimensions,
              '%width' => $image->getWidth(),
              '%height' => $image->getHeight(),
            ]);
          return;
        }
        $this->context->addViolation($constraint->messageImageTooSmall,
          [
            '%dimensions' => $minDimensions,
            '%width' => $image->getWidth(),
            '%height' => $image->getHeight(),
          ]);
      }
    }
  }

}
