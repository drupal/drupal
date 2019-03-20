<?php

namespace Drupal\KernelTests\Core\File;

/**
 * Tests file_create_filename().
 *
 * @group File
 */
class FileCreateFilenameTest extends FileTestBase {

  /**
   * Tests that invalid UTF-8 does not break file_create_filename().
   */
  public function testInvalidUTF8() {
    $filename = "a\xFFsdf\x80€" . '.txt';
    $this->setExpectedException(\RuntimeException::class, "Invalid filename '$filename'");
    file_create_filename($filename, $this->siteDirectory);
  }

}
