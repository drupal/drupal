<?php

/**
 * @file
 * Contains \Drupal\Core\Form\Exception\BrokenPostRequestException.
 */

namespace Drupal\Core\Form\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Defines an exception used, when the POST HTTP body is broken.
 */
class BrokenPostRequestException extends BadRequestHttpException {

  /**
   * The maximum upload size.
   *
   * @var string
   */
  protected $size;

  /**
   * Constructs a new BrokenPostRequestException.
   *
   * @param string $max_upload_size
   *   The size of the maximum upload size.
   * @param string $message
   *   The internal exception message.
   * @param \Exception $previous
   *   The previous exception.
   * @param int $code
   *   The internal exception code.
   */
  public function __construct($max_upload_size, $message = NULL, \Exception $previous = NULL, $code = 0) {
    parent::__construct($message, $previous, $code);

    $this->size = $max_upload_size;
  }

  /**
   * Returns the maximum upload size.
   *
   * @return string
   *   A translated string representation of the size of the file size limit
   *   based on the PHP upload_max_filesize and post_max_size.
   */
  public function getSize() {
    return $this->size;
  }


}
