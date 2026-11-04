<?php

namespace Drupal\Core\File;

/**
 * A value object representing a protected directory.
 */
class ProtectedDirectory {

  /**
   * ProtectedDirectory constructor.
   *
   * @param string $title
   *   The directory title.
   * @param string $path
   *   The path to the directory.
   * @param bool $private
   *   (optional) Whether the directory is private or public (default).
   */
  public function __construct(
    protected $title,
    protected $path,
    protected $private = FALSE,
  ) {
  }

  /**
   * Gets the title.
   *
   * @return string
   *   The Title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Gets the directory path.
   *
   * @return string
   *   The directory path.
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Is the directory private (or public).
   *
   * @return bool
   *   TRUE if the directory is private, FALSE if it is public.
   */
  public function isPrivate() {
    return $this->private;
  }

}
