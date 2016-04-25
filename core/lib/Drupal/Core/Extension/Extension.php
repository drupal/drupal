<?php

namespace Drupal\Core\Extension;

/**
 * Defines an extension (file) object.
 */
class Extension implements \Serializable {

  /**
   * The type of the extension (e.g., 'module').
   *
   * @var string
   */
  protected $type;

  /**
   * The relative pathname of the extension (e.g., 'core/modules/node/node.info.yml').
   *
   * @var string
   */
  protected $pathname;

  /**
   * The filename of the main extension file (e.g., 'node.module').
   *
   * @var string|null
   */
  protected $filename;

  /**
   * An SplFileInfo instance for the extension's info file.
   *
   * Note that SplFileInfo is a PHP resource and resources cannot be serialized.
   *
   * @var \SplFileInfo
   */
  protected $splFileInfo;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Constructs a new Extension object.
   *
   * @param string $root
   *   The app root.
   * @param string $type
   *   The type of the extension; e.g., 'module'.
   * @param string $pathname
   *   The relative path and filename of the extension's info file; e.g.,
   *   'core/modules/node/node.info.yml'.
   * @param string $filename
   *   (optional) The filename of the main extension file; e.g., 'node.module'.
   */
  public function __construct($root, $type, $pathname, $filename = NULL) {
    $this->root = $root;
    $this->type = $type;
    $this->pathname = $pathname;
    $this->filename = $filename;
  }

  /**
   * Returns the type of the extension.
   *
   * @return string
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Returns the internal name of the extension.
   *
   * @return string
   */
  public function getName() {
    return basename($this->pathname, '.info.yml');
  }

  /**
   * Returns the relative path of the extension.
   *
   * @return string
   */
  public function getPath() {
    return dirname($this->pathname);
  }

  /**
   * Returns the relative path and filename of the extension's info file.
   *
   * @return string
   */
  public function getPathname() {
    return $this->pathname;
  }

  /**
   * Returns the filename of the extension's info file.
   *
   * @return string
   */
  public function getFilename() {
    return basename($this->pathname);
  }

  /**
   * Returns the relative path of the main extension file, if any.
   *
   * @return string|null
   */
  public function getExtensionPathname() {
    if ($this->filename) {
      return $this->getPath() . '/' . $this->filename;
    }
  }

  /**
   * Returns the name of the main extension file, if any.
   *
   * @return string|null
   */
  public function getExtensionFilename() {
    return $this->filename;
  }

  /**
   * Loads the main extension file, if any.
   *
   * @return bool
   *   TRUE if this extension has a main extension file, FALSE otherwise.
   */
  public function load() {
    if ($this->filename) {
      include_once $this->root . '/' . $this->getPath() . '/' . $this->filename;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Re-routes method calls to SplFileInfo.
   *
   * Offers all SplFileInfo methods to consumers; e.g., $extension->getMTime().
   */
  public function __call($method, array $args) {
    if (!isset($this->splFileInfo)) {
      $this->splFileInfo = new \SplFileInfo($this->pathname);
    }
    return call_user_func_array(array($this->splFileInfo, $method), $args);
  }

  /**
   * Implements Serializable::serialize().
   *
   * Serializes the Extension object in the most optimized way.
   */
  public function serialize() {
    // Don't serialize the app root, since this could change if the install is
    // moved.
    $data = array(
      'type' => $this->type,
      'pathname' => $this->pathname,
      'filename' => $this->filename,
    );

    // @todo ThemeHandler::listInfo(), ThemeHandler::rebuildThemeData(), and
    //   system_list() are adding custom properties to the Extension object.
    $info = new \ReflectionObject($this);
    foreach ($info->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
      $data[$property->getName()] = $property->getValue($this);
    }

    return serialize($data);
  }

  /**
   * {@inheritdoc}
   */
  public function unserialize($data) {
    $data = unserialize($data);
    // Get the app root from the container.
    $this->root = DRUPAL_ROOT;
    $this->type = $data['type'];
    $this->pathname = $data['pathname'];
    $this->filename = $data['filename'];

    // @todo ThemeHandler::listInfo(), ThemeHandler::rebuildThemeData(), and
    //   system_list() are adding custom properties to the Extension object.
    foreach ($data as $property => $value) {
      if (!isset($this->$property)) {
        $this->$property = $value;
      }
    }
  }

}
