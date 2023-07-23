<?php

namespace Drupal\file\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * File name length constraint.
 *
 * @Constraint(
 *   id = "FileNameLength",
 *   label = @Translation("File Name Length", context = "Validation"),
 *   type = "file"
 * )
 */
class FileNameLengthConstraint extends Constraint {

  /**
   * The maximum file name length.
   *
   * @var int
   */
  public int $maxLength = 240;

  /**
   * The message when file name is empty.
   *
   * @var string
   */
  public string $messageEmpty = "The file's name is empty. Enter a name for the file.";

  /**
   * The message when file name is too long.
   *
   * @var string
   */
  public string $messageTooLong = "The file's name exceeds the %maxLength characters limit. Rename the file and try again.";

}
