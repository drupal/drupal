<?php

namespace Drupal\Component\FileSystem;

/**
 * Iterates over files whose names match a regular expression in a directory.
 */
class RegexDirectoryIterator extends \FilterIterator {

  /**
   * The regular expression to match.
   *
   * @var string
   */
  protected $regex;

  /**
   * RegexDirectoryIterator constructor.
   *
   * @param string $path
   *   The path to scan.
   * @param string $regex
   *   The regular expression to match, including delimiters. For example,
   *   /\.yml$/ would list only files ending in .yml.
   */
  public function __construct($path, $regex) {
    // Use FilesystemIterator to not iterate over the the . and .. directories.
    $iterator = new \FilesystemIterator($path);
    parent::__construct($iterator);
    $this->regex = $regex;
  }

  /**
   * Implements \FilterIterator::accept().
   */
  public function accept() {
    /** @var \SplFileInfo $file_info */
    $file_info = $this->getInnerIterator()->current();
    return $file_info->isFile() && preg_match($this->regex, $file_info->getFilename());
  }

}
