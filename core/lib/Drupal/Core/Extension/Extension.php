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
   * @todo Replace all uses of $type with getType() method.
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
   * @todo Replace all uses of $name with getName() method.
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
   * The filename of the main extension file (e.g., 'node.module').
   *
   * Note that this is not necessarily a filename but a pathname and also not
   * necessarily the filename of the info file. Due to legacy code and property
   * value overloading, it is either the filename of the main extension file or
   * the relative pathname of the main extension file (== $uri), depending on
   * whether the object has been post-processed or not.
   *
   * @see _system_rebuild_module_data()
   * @see \Drupal\Core\Extension\ThemeHandler::rebuildThemeData()
   *
   * @todo Remove this property and do not require .module/.profile files.
   * @see https://drupal.org/node/340723
   *
   * @var string
   */
  public $filename;

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
   *   The filename of the main extension file; e.g., 'node.module'.
   */
  public function __construct($type, $pathname, $filename) {
    $this->type = $type;
    $this->pathname = $pathname;
    // Set legacy public properties.
    $this->name = basename($pathname, '.info.yml');
    $this->filename = $filename;
    $this->uri = dirname($pathname) . '/' . $filename;
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
   * Sets an explicit SplFileInfo object for the extension's info file.
   *
   * Used by ExtensionDiscovery::scanDirectory() to avoid creating additional
   * PHP resources.
   *
   * @param \SplFileInfo $fileinfo
   *   A file info instance to set.
   *
   * @return $this
   */
  public function setSplFileInfo(\SplFileInfo $fileinfo) {
    $this->splFileInfo = $fileinfo;
    return $this;
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
    );

    // Include legacy public properties.
    // @todo Remove this property and do not require .module/.profile files.
    // @see https://drupal.org/node/340723
    // @see Extension::$filename
    $data['filename'] = basename($this->uri);

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

    // Restore legacy public properties.
    // @todo Remove these properties and do not require .module/.profile files.
    // @see https://drupal.org/node/340723
    // @see Extension::$filename
    $this->name = basename($data['pathname'], '.info.yml');
    $this->uri = dirname($data['pathname']) . '/' . $data['filename'];
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
