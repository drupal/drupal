<?php

/**
 * @file
 * Contains \Drupal\Core\Extension\Extension.
 */

namespace Drupal\Core\Extension;

/**
 * Defines an extension (file) object.
 */
class Extension implements \Serializable {

  /**
   * The type of the extension (e.g., 'module').
   *
   * @todo Change to protected once external test dependencies are resolved.
   *
   * @var string
   */
  public $type;

  /**
   * The relative pathname of the extension (e.g., 'core/modules/node/node.info.yml').
   *
   * @var string
   */
  protected $pathname;

  /**
   * The internal name of the extension (e.g., 'node').
   *
   * @todo Remove this property once external test dependencies are resolved.
   *
   * @var string
   */
  public $name;

  /**
   * The relative pathname of the main extension file (e.g., 'core/modules/node/node.module').
   *
   * @todo Remove this property and do not require .module/.profile files.
   * @see https://drupal.org/node/340723
   *
   * @var string
   */
  public $uri;

  /**
   * Same as $uri.
   *
   * @todo Remove this property once external test dependencies are resolved.
   *
   * @var string
   */
  public $filename;

  /**
   * The filename of the main extension file (e.g., 'node.module').
   *
   * @todo Rename to $filename once external test dependencies are resolved.
   *
   * @var string|null
   */
  protected $_filename;

  /**
   * An SplFileInfo instance for the extension's info file.
   *
   * Note that SplFileInfo is a PHP resource and resources cannot be serialized.
   *
   * @var \SplFileInfo
   */
  protected $splFileInfo;

  /**
   * Constructs a new Extension object.
   *
   * @param string $type
   *   The type of the extension; e.g., 'module'.
   * @param string $pathname
   *   The relative path and filename of the extension's info file; e.g.,
   *   'core/modules/node/node.info.yml'.
   * @param string $filename
   *   (optional) The filename of the main extension file; e.g., 'node.module'.
   */
  public function __construct($type, $pathname, $filename = NULL) {
    $this->type = $type;
    $this->pathname = $pathname;
    $this->_filename = $filename;
    // Set legacy public properties.
    $this->name = $this->getName();
    $this->uri = $this->getPath() . '/' . $filename;
    $this->filename = $this->uri;
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
    if ($this->_filename) {
      return $this->getPath() . '/' . $this->_filename;
    }
  }

  /**
   * Returns the name of the main extension file, if any.
   *
   * @return string|null
   */
  public function getExtensionFilename() {
    return $this->_filename;
  }

  /**
   * Loads the main extension file, if any.
   *
   * @return bool
   *   TRUE if this extension has a main extension file, FALSE otherwise.
   */
  public function load() {
    if ($this->_filename) {
      include_once DRUPAL_ROOT . '/' . $this->getPath() . '/' . $this->_filename;
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
    $data = array(
      'type' => $this->type,
      'pathname' => $this->pathname,
      '_filename' => $this->_filename,
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
   * Implements Serializable::unserialize().
   */
  public function unserialize($data) {
    $data = unserialize($data);
    $this->type = $data['type'];
    $this->pathname = $data['pathname'];
    $this->_filename = $data['_filename'];

    // Restore legacy public properties.
    // @todo Remove these properties and do not require .module/.profile files.
    // @see https://drupal.org/node/340723
    $this->name = $this->getName();
    $this->uri = $this->getPath() . '/' . $this->_filename;
    $this->filename = $this->uri;

    // @todo ThemeHandler::listInfo(), ThemeHandler::rebuildThemeData(), and
    //   system_list() are adding custom properties to the Extension object.
    foreach ($data as $property => $value) {
      if (!isset($this->$property)) {
        $this->$property = $value;
      }
    }
  }

}
