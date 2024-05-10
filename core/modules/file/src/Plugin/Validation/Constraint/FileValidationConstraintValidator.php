<?php

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\file\Validation\FileValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks that a file referenced in a file field is valid.
 */
class FileValidationConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Creates a new FileValidationConstraintValidator.
   *
   * @param \Drupal\file\Validation\FileValidatorInterface $fileValidator
   *   The file validator.
   */
  public function __construct(
    protected FileValidatorInterface $fileValidator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('file.validator'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    // Get the file to execute validators.
    $target = $value->get('entity')->getTarget();
    if (!$target) {
      return;
    }

    $file = $target->getValue();
    // Get the validators.
    $validators = $value->getUploadValidators();

    // Always respect the configured maximum file size.
    $field_settings = $value->getFieldDefinition()->getSettings();
    if (array_key_exists('max_filesize', $field_settings)) {
      $validators['FileSizeLimit'] = ['fileLimit' => Bytes::toNumber($field_settings['max_filesize'])];
    }
    else {
      // Do not validate the file size if it is not set explicitly.
      unset($validators['FileSizeLimit']);
    }

    // Checks that a file meets the criteria specified by the validators.
    if ($violations = $this->fileValidator->validate($file, $validators)) {
      foreach ($violations as $violation) {
        $this->context->addViolation($violation->getMessage());
      }
    }
  }

}
