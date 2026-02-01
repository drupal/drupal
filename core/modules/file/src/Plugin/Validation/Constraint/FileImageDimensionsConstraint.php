<?php

declare(strict_types=1);

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * File extension dimensions constraint.
 */
#[Constraint(
  id: 'FileImageDimensions',
  label: new TranslatableMarkup('File Image Dimensions', [], ['context' => 'Validation']),
  type: 'file'
)]
class FileImageDimensionsConstraint extends SymfonyConstraint {

  /**
   * The minimum dimensions.
   *
   * @var string|int
   */
  public string | int $minDimensions = 0;

  /**
   * The maximum dimensions.
   *
   * @var string|int
   */
  public string | int $maxDimensions = 0;

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    string|int|null $minDimensions = NULL,
    string|int|null $maxDimensions = NULL,
    public string $messageResizedImageTooSmall = 'The resized image is too small. The minimum dimensions are %dimensions pixels and after resizing, the image size will be %widthx%height pixels.',
    public string $messageImageTooSmall = 'The image is too small. The minimum dimensions are %dimensions pixels and the image size is %widthx%height pixels.',
    public string $messageResizeFailed = 'The image exceeds the maximum allowed dimensions and an attempt to resize it failed.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
    $this->minDimensions = $minDimensions ?? $this->minDimensions;
    $this->maxDimensions = $maxDimensions ?? $this->maxDimensions;
  }

}
