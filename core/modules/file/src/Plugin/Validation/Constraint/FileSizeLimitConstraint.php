<?php

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * File size max constraint.
 */
#[Constraint(
  id: 'FileSizeLimit',
  label: new TranslatableMarkup('File Size Limit', [], ['context' => 'Validation']),
  type: 'file'
)]
class FileSizeLimitConstraint extends SymfonyConstraint {

  /**
   * The message for when file size limit is exceeded.
   *
   * @var string
   */
  public string $maxFileSizeMessage = 'The file is %filesize exceeding the maximum file size of %maxsize.';

  /**
   * The message for when disk quota is exceeded.
   *
   * @var string
   */
  public string $diskQuotaMessage = 'The file is %filesize which would exceed your disk quota of %quota.';

  /**
   * The file limit.
   *
   * @var int
   */
  public int $fileLimit = 0;

  /**
   * The user limit.
   *
   * @var int
   */
  public int $userLimit = 0;

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'fileLimit';
  }

}
