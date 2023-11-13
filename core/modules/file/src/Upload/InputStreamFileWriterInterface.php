<?php

namespace Drupal\file\Upload;

/**
 * Uploads files from a stream.
 */
interface InputStreamFileWriterInterface {


  /**
   * The length of bytes to read in each iteration when streaming file data.
   */
  const DEFAULT_BYTES_TO_READ = 8192;

  /**
   * The default stream.
   */
  const DEFAULT_STREAM = "php://input";

  /**
   * Write the input stream to a temporary file.
   *
   * @param string $stream
   *   (optional) The input stream.
   * @param int $bytesToRead
   *   (optional) The length of bytes to read in each iteration.
   *
   * @return string
   *   The temporary file path.
   */
  public function writeStreamToFile(string $stream = self::DEFAULT_STREAM, int $bytesToRead = self::DEFAULT_BYTES_TO_READ): string;

}
