<?php
// $Id$

/**
 * @file
 * Archiver implementations provided by the system module.
 */

/**
 * Archiver for .tar files.
 */
class ArchiverTar implements ArchiverInterface {

  /**
   * The underlying Archive_Tar instance that does the heavy lifting.
   *
   * @var Archive_Tar
   */
  protected $tar;

  public function __construct($file_path) {
    $this->tar = new Archive_Tar($file_path);
  }

  public function add($file_path) {
    $this->tar->add($file_path);

    return $this;
  }

  public function remove($path) {
    // @todo Archive_Tar doesn't have a remove operation
    // so we'll have to simulate it somehow, probably by
    // creating a new archive with everything but the removed
    // file.

    return $this;
  }

  public function extract($path, Array $files = array()) {
    if ($files) {
      $this->tar->extractList($files, $path);
    }
    else {
      $this->tar->extract($path);
    }

    return $this;
  }

  public function listContents() {
    return $this->tar->listContent();
  }

  /**
   * Retrieve the tar engine itself.
   *
   * In some cases it may be necessary to directly access the underlying
   * Archive_Tar object for implementation-specific logic. This is for advanced
   * use only as it is not shared by other implementations of ArchiveInterface.
   *
   * @return
   *   The Archive_Tar object used by this object.
   */
  public function getArchive() {
    return $this->tar;
  }
}
