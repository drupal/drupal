<?php

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
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

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    ?int $fileLimit = NULL,
    ?int $userLimit = NULL,
    public string $maxFileSizeMessage = 'The file is %filesize exceeding the maximum file size of %maxsize.',
    public string $diskQuotaMessage = 'The file is %filesize which would exceed your disk quota of %quota.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
    $this->fileLimit = $fileLimit ?? $this->fileLimit;
    $this->userLimit = $userLimit ?? $this->userLimit;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'fileLimit';
  }

}
