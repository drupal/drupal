<?php

namespace Drupal\file\Upload;

use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\HttpFoundation\File\Exception\CannotWriteFileException;
use Symfony\Component\HttpFoundation\File\Exception\NoFileException;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;

/**
 * Writes files from a input stream to a temporary file.
 */
class InputStreamFileWriter implements InputStreamFileWriterInterface {

  /**
   * Creates a new InputStreamFileUploader.
   */
  public function __construct(
    protected FileSystemInterface $fileSystem,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function writeStreamToFile(string $stream = self::DEFAULT_STREAM, int $bytesToRead = self::DEFAULT_BYTES_TO_READ): string {
    // 'rb' is needed so reading works correctly on Windows environments too.
    $fileData = fopen($stream, 'rb');

    $tempFilePath = $this->fileSystem->tempnam('temporary://', 'file');
    $tempFile = fopen($tempFilePath, 'wb');

    if ($tempFile) {
      while (!feof($fileData)) {
        $read = fread($fileData, $bytesToRead);

        if ($read === FALSE) {
          // Close the file streams.
          fclose($tempFile);
          fclose($fileData);
          throw new UploadException('Input file data could not be read');
        }

        if (fwrite($tempFile, $read) === FALSE) {
          // Close the file streams.
          fclose($tempFile);
          fclose($fileData);
          throw new CannotWriteFileException(sprintf('Temporary file data for "%s" could not be written', $tempFilePath));
        }
      }

      // Close the temp file stream.
      fclose($tempFile);
    }
    else {
      // Close the input file stream since we can't proceed with the upload.
      // Don't try to close $tempFile since it's FALSE at this point.
      fclose($fileData);
      throw new NoFileException(sprintf('Temporary file "%s" could not be opened for file upload', $tempFilePath));
    }

    // Close the input stream.
    fclose($fileData);

    return $tempFilePath;
  }

}
