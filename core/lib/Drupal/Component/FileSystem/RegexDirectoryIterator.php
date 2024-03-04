<?php

namespace Drupal\Component\FileSystem;

/**
 * Iterates over files whose names match a regular expression in a directory.
 */
class RegexDirectoryIterator extends \RegexIterator {

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
    parent::__construct(new \FilesystemIterator($path), $regex);
  }

  /**
   * Implements \RegexIterator::accept().
   */
  #[\ReturnTypeWillChange]
  public function accept() {
    /** @var \SplFileInfo $file_info */
    $file_info = $this->getInnerIterator()->current();
    return $file_info->isFile() && preg_match($this->getRegex(), $file_info->getFilename());
  }

}
