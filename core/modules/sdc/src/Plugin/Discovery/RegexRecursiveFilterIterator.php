<?php

namespace Drupal\sdc\Plugin\Discovery;

/**
 * Iterates over files whose names match a regular expression in a directory.
 *
 * @internal
 */
final class RegexRecursiveFilterIterator extends \RecursiveFilterIterator {

  /**
   * RegexDirectoryIterator constructor.
   *
   * @param \RecursiveIterator $iterator
   *   The iterator.
   * @param string $regex
   *   The regular expression to match, including delimiters. For example,
   *   /\.yml$/ would list only files ending in .yml.
   */
  public function __construct(\RecursiveIterator $iterator, protected string $regex = '') {
    parent::__construct($iterator);
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function accept() {
    /** @var \SplFileInfo $file_info */
    $file_info = $this->getInnerIterator()->current();
    if ($file_info->isDir()) {
      // Enter into subdirectory.
      return TRUE;
    }
    // Return if file matches regular expression.
    return $file_info->isFile() && preg_match($this->regex, $file_info->getFilename());
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function getChildren() {
    $children = parent::getChildren();
    if ($children instanceof self && empty($children->regex)) {
      $children->regex = $this->regex;
    }
    return $children;
  }

}
