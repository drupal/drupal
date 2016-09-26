<?php

namespace Drupal\Tests;

use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Provides methods to create test files from given values.
 *
 * This trait is meant to be used only by test classes.
 */
trait TestFileCreationTrait {

  /**
   * Whether the files were copied to the test files directory.
   *
   * @var bool
   */
  protected $generatedTestFiles = FALSE;

  /**
   * Gets a list of files that can be used in tests.
   *
   * The first time this method is called, it will call
   * $this->generateFile() to generate binary and ASCII text files in the
   * public:// directory. It will also copy all files in
   * core/modules/simpletest/files to public://. These contain image, SQL, PHP,
   * JavaScript, and HTML files.
   *
   * All filenames are prefixed with their type and have appropriate extensions:
   * - text-*.txt
   * - binary-*.txt
   * - html-*.html and html-*.txt
   * - image-*.png, image-*.jpg, and image-*.gif
   * - javascript-*.txt and javascript-*.script
   * - php-*.txt and php-*.php
   * - sql-*.txt and sql-*.sql
   *
   * Any subsequent calls will not generate any new files, or copy the files
   * over again. However, if a test class adds a new file to public:// that
   * is prefixed with one of the above types, it will get returned as well, even
   * on subsequent calls.
   *
   * @param $type
   *   File type, possible values: 'binary', 'html', 'image', 'javascript',
   *   'php', 'sql', 'text'.
   * @param $size
   *   (optional) File size in bytes to match. Defaults to NULL, which will not
   *   filter the returned list by size.
   *
   * @return array[]
   *   List of files in public:// that match the filter(s).
   */
  protected function getTestFiles($type, $size = NULL) {
    if (empty($this->generatedTestFiles)) {
      // Generate binary test files.
      $lines = [64, 1024];
      $count = 0;
      foreach ($lines as $line) {
        $this->generateFile('binary-' . $count++, 64, $line, 'binary');
      }

      // Generate ASCII text test files.
      $lines = [16, 256, 1024, 2048, 20480];
      $count = 0;
      foreach ($lines as $line) {
        $this->generateFile('text-' . $count++, 64, $line, 'text');
      }

      // Copy other test files from simpletest.
      $original = drupal_get_path('module', 'simpletest') . '/files';
      $files = file_scan_directory($original, '/(html|image|javascript|php|sql)-.*/');
      foreach ($files as $file) {
        file_unmanaged_copy($file->uri, PublicStream::basePath());
      }

      $this->generatedTestFiles = TRUE;
    }

    $files = [];
    // Make sure type is valid.
    if (in_array($type, ['binary', 'html', 'image', 'javascript', 'php', 'sql', 'text'])) {
      $files = file_scan_directory('public://', '/' . $type . '\-.*/');

      // If size is set then remove any files that are not of that size.
      if ($size !== NULL) {
        foreach ($files as $file) {
          $stats = stat($file->uri);
          if ($stats['size'] != $size) {
            unset($files[$file->uri]);
          }
        }
      }
    }
    usort($files, [$this, 'compareFiles']);
    return $files;
  }

  /**
   * Compares two files based on size and file name.
   *
   * Callback for uasort() within \TestFileCreationTrait::getTestFiles().
   *
   * @param \stdClass $file1
   *   The first file.
   * @param \stdClass $file2
   *   The second class.
   *
   * @return int
   */
  protected function compareFiles($file1, $file2) {
    $compare_size = filesize($file1->uri) - filesize($file2->uri);
    if ($compare_size) {
      // Sort by file size.
      return $compare_size;
    }
    else {
      // The files were the same size, so sort alphabetically.
      return strnatcmp($file1->name, $file2->name);
    }
  }

  /**
   * Generates a test file.
   *
   * @param string $filename
   *   The name of the file, including the path. The suffix '.txt' is appended
   *   to the supplied file name and the file is put into the public:// files
   *   directory.
   * @param int $width
   *   The number of characters on one line.
   * @param int $lines
   *   The number of lines in the file.
   * @param string $type
   *   (optional) The type, one of:
   *   - text: The generated file contains random ASCII characters.
   *   - binary: The generated file contains random characters whose codes are
   *     in the range of 0 to 31.
   *   - binary-text: The generated file contains random sequence of '0' and '1'
   *     values.
   *
   * @return string
   *   The name of the file, including the path.
   */
  public static function generateFile($filename, $width, $lines, $type = 'binary-text') {
    $text = '';
    for ($i = 0; $i < $lines; $i++) {
      // Generate $width - 1 characters to leave space for the "\n" character.
      for ($j = 0; $j < $width - 1; $j++) {
        switch ($type) {
          case 'text':
            $text .= chr(rand(32, 126));
            break;
          case 'binary':
            $text .= chr(rand(0, 31));
            break;
          case 'binary-text':
          default:
            $text .= rand(0, 1);
            break;
        }
      }
      $text .= "\n";
    }

    // Create filename.
    file_put_contents('public://' . $filename . '.txt', $text);
    return $filename;
  }

}
