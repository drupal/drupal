<?php

declare(strict_types=1);

namespace Drupal\Core\Form\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Defines an exception used, when the POST HTTP body is broken.
 */
class BrokenPostRequestException extends BadRequestHttpException {

  /**
   * Constructs a new BrokenPostRequestException.
   *
   * @param int $size
   *   The size of the maximum upload size in bytes.
   * @param string $message
   *   The internal exception message.
   * @param \Throwable|null $previous
   *   The previous exception.
   * @param int $code
   *   The internal exception code.
   */
  public function __construct(
    protected int $size,
    string $message = '',
    ?\Throwable $previous = NULL,
    int $code = 0,
  ) {
    parent::__construct($message, $previous, $code);
  }

  /**
   * Returns the maximum upload size in bytes.
   *
   * @return int
   *   The file size limit in bytes based on the PHP upload_max_filesize and
   *   post_max_size.
   */
  public function getSize(): int {
    return $this->size;
  }

}
