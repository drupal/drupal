<?php
// $Id: system.archiver.inc,v 1.4 2010/02/01 19:14:14 dries Exp $

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
    $files = array();
    foreach ($this->tar->listContent() as $file_data) {
      $files[] = $file_data['filename'];
    }
    return $files;
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

/**
 * Archiver for .zip files.
 *
 * @link http://php.net/zip
 */
class ArchiverZip implements ArchiverInterface {

  /**
   * The underlying ZipArchive instance that does the heavy lifting.
   *
   * @var ZipArchive
   */
  protected $zip;

  public function __construct($file_path) {
    $this->zip = new ZipArchive();
    if ($this->zip->open($file_path) !== TRUE) {
      // @todo: This should be an interface-specific exception some day.
      throw new Exception(t('Cannot open %file_path', array('%file_path' => $file_path)));
    }
  }

  public function add($file_path) {
    $this->zip->addFile($file_path);

    return $this;
  }

  public function remove($path) {
    $this->zip->deleteName($file_path);

    return $this;
  }

  public function extract($path, Array $files = array()) {
    $this->zip->extractTo($path, $files);

    return $this;
  }

  public function listContents() {
    $files = array();
    for ($i=0; $i < $this->zip->numFiles; $i++) {
      $files[] = $this->zip->getNameIndex($i);
    }
    return $files;
  }

  /**
   * Retrieve the zip engine itself.
   *
   * In some cases it may be necessary to directly access the underlying
   * ZipArchive object for implementation-specific logic. This is for advanced
   * use only as it is not shared by other implementations of ArchiveInterface.
   *
   * @return
   *   The ZipArchive object used by this object.
   */
  public function getArchive() {
    return $this->zip;
  }
}
