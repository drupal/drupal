<?php

declare(strict_types=1);

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validator for the FileExtensionSecureConstraint.
 */
class FileExtensionSecureConstraintValidator extends BaseFileConstraintValidator implements ContainerInjectionInterface {

  /**
   * Creates a new FileExtensionSecureConstraintValidator.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint) {
    $file = $this->assertValueIsFile($value);
    if (!$constraint instanceof FileExtensionSecureConstraint) {
      throw new UnexpectedTypeException($constraint, FileExtensionSecureConstraint::class);
    }

    $allowInsecureUploads = $this->configFactory->get('system.file')->get('allow_insecure_uploads');
    if (!$allowInsecureUploads && preg_match(FileSystemInterface::INSECURE_EXTENSION_REGEX, $file->getFilename())) {
      $this->context->addViolation($constraint->message);
    }
  }

}
