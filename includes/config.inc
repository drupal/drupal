<?php

/**
 * @file
 * This is the API for configuration storage.
 */

/**
 * Get the randomly generated config directory name.
 *
 * @return
 *   The directory name.
 */
function config_get_config_directory() {
  global $drupal_config_directory_name;
  
  return conf_path() . '/files/' . $drupal_config_directory_name;
}

/**
 * Retrieve an iterable array which lists the children under a config 'branch'.
 *
 * Given the following configuration files:
 *   core.entity.node_type.article.xml
 *   core.entity.node_type.page.xml
 *
 * You can pass a prefix 'core.entity.node_type' and get back an array of the
 * filenames that match. This allows you to iterate through all files in a 
 * branch. Note that this will only work on the level above the tips, so
 * a prefix of 'core.entity' would return an empty array.
 *
 * @param $prefix
 *   The prefix of the files we are searching for.
 * @return
 *   An array of file names under a branch.
 */
function config_get_signed_file_storage_names_with_prefix($prefix = '') {
  $files = glob(config_get_config_directory() . '/' . $prefix . '*.xml');
  $clean_name = function ($value) {
    return basename($value, '.xml');
  };
  return array_map($clean_name, $files);
}

/**
 * Write our signing key out to the filesystem.
 *
 * @param $force_rekey
 *   Boolean to specify whether we should force a new key to be generated
 *   and written, even if a key file exists already.
 */
function config_write_signed_file_storage_key($force_rekey = FALSE) {
  $file_path = conf_path() . '/key.php';
  if (!file_exists($file_path) || $force_rekey) {
    $key = drupal_hash_base64(drupal_random_bytes(55));
    if (!file_put_contents($file_path, '<?php die(); ' . $key . "\n")) {
      throw new Exception('Failed to write configuration signing key.');
    }
  }
}

/**
 * Generate a hash of a config file's contents using our encryption key.
 * 
 * @param $data
 *   The contents of a configuration file.
 * @return
 *   A hash of the data.
 */
function config_sign_data($data) {
  $file = file_get_contents(conf_path() . '/key.php');
  if ($file === FALSE) {
    throw new Exception('Key file not found.');
  }

  // Strip the header from the file contents.
  $parts = explode(' ', $file);
  $key = $parts[2];
  
  // SHA-512 is both secure and very fast on 64 bit CPUs.
  return hash_hmac('sha512', $data, $key);
}

class ConfigException extends Exception {}

class ConfigFileStorageException extends ConfigException {}
class ConfigFileStorageReadException extends ConfigFileStorageException {}
class ConfigFileStorageSignatureException extends ConfigFileStorageException {}

class SignedFileStorage {
  /**
   * Constructor for the signed file storage interface.
   *
   * This class allows reading and writing configuration data from/to the
   * disk while automatically managing and verifying cryptographic signatures.
   *
   * param @name
   *   Lowercase string, the name for the configuration data.
   */
  public function __construct($name) {
    $this->name = $name;
  }

  /**
   * Read and return a signed file and its signature.
   *
   * @return
   *   An array with "signature" and "data" keys.
   *
   * @throws
   *   Exception
   */
  protected function readWithSignature() {
    // TODO: Optimize with explicit offsets?
    $content = file_get_contents($this->getFilePath());
    if ($content === FALSE) {
      throw new Exception('Read file is invalid.');
    }
    $signature = file_get_contents($this->getFilePath() . '.sig');
    if ($signature === FALSE) {
      throw new Exception('Signature file is invalid.');
    }
    return array('data' => $content, 'signature' => $signature);
  }

  protected function exists() {
    return file_exists($this->getFilePath());
  }
  
  public function getFilePath() {
    return config_get_config_directory() . '/' . $this->name  . '.xml';
  }

  public function resign() {
    if ($this->exists()) {
      $parts = $this->readWithSignature();
      $this->write($parts['data']);
    }
  }

  public function verify($contentOnSuccess = FALSE) {
    if ($this->exists()) {
      $split = $this->readWithSignature();
      $expected_signature = config_sign_data($split['data']);
      if ($expected_signature === $split['signature']) {
        if ($contentOnSuccess) {
          return $split['data'];
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  public function write($data) {
    $signature = config_sign_data($data);
    if (!file_put_contents($this->getFilePath(), $data)) {
      throw new Exception('Failed to write configuration file.');
    }
    if (!file_put_contents($this->getFilePath() . '.sig', $signature)) {
      throw new Exception('Failed to write signature file.');
    }
  }

  public function read() {
    if ($this->exists()) {
      $verification = $this->verify(TRUE);
      if ($verification === FALSE) {
        throw new Exception('Invalid signature in file header.');
      }
      return $verification;
    }
  }
}

interface DrupalConfigVerifiedStorageInterface {

  /**
   * Constructor for the verified storage manipulation class.
   *
   * This class allows reading and writing configuration data from/to the
   * verified storage and copying to/from the signed file storing the same
   * data.
   *
   * param @name
   *   Lowercase string, the name for the configuration data.
   */
  function __construct($name);

  /**
   * Read the configuration data from the verified storage.
   */
  function read();

  /**
   * Copy the configuration data from the verified storage into a file.
   */
  function copyToFile();

  /**
   * Copy the configuration data from the file into the verified storage.
   */
  function copyFromFile();

  /**
   * Check whether the file and the verified storage is in sync.
   *
   * @return
   *   TRUE if the file and the verified storage contains the same data, FALSE
   *   if not.
   */
  function isOutOfSync();

  /**
   * Write the configuration data into the active storage but not the file.
   *
   * Use this function if you need to make temporary changes to your
   * configuration.
   */
  function writeToActive($data);

  /**
   * Write the configuration data into the active storage and the file.
   */
  function write($data);

  /**
   * Get names starting with this prefix.
   */
  static function getNamesWithPrefix($prefix);
}

abstract class DrupalConfigVerifiedStorage implements DrupalConfigVerifiedStorageInterface {
  function __construct($name) {
    $this->name = $name;
  }

  protected function signedFileStorage() {
    return new SignedFileStorage($this->name);
  }

  public function copyTofile() {
    return $this->signedFileStorage()->write($this->read());
  }

  public function copyFromFile() {
    return $this->writeToActive($this->readFromFile());
  }

  public function readFromFile() {
    return $this->signedFileStorage()->read($this->name);
  }

  public function isOutOfSync() {
    return $this->read() !== $this->readFromFile();
  }

  public function write($data) {
    $this->writeToActive($data);
    $this->copyToFile();
  }
}

class DrupalVerifiedStorageSQL extends DrupalConfigVerifiedStorage {

  public function read() {
    return db_query('SELECT data FROM {config} WHERE name = :name', array(':name' => $this->name))->fetchField();
  }

  public function writeToActive($data) {
    return db_merge('config')
      ->key(array('name' => $this->name))
      ->fields(array('data' => $data))
      ->execute();
  }

  static public function getNamesWithPrefix($prefix = '') {
    return db_query('SELECT name FROM {config} WHERE name LIKE :name', array(':name' => db_like($prefix) . '%'))->fetchCol();
  }
}

function config_get_verified_storage_names_with_prefix($prefix = '') {
  return DrupalVerifiedStorageSQL::getNamesWithPrefix($prefix);
}

function config_get_names_with_prefix($prefix) {
  return config_get_verified_storage_names_with_prefix($prefix);
}

function config($name, $class = 'DrupalConfig') {
  static $overrides;
  if (!isset($overrides)) {
    $storage = new SignedFileStorage('local');
    $overrides = (array) config_decode($storage->read());
  }
  $key_overrides = isset($overrides[$name]) ? $overrides[$name] : array();
  // @TODO Replace this with the appropriate factory.
  return new $class(new DrupalVerifiedStorageSQL($name), $key_overrides);
}

/**
 * Decode configuration data from its native format to an associative array.
 *
 * @param $data
 *   Configuration data
 * @return
 *   An associative array representation of the data.
 */
function config_decode($data) {
  $xml = new SimpleXMLElement($data);
  $json = json_encode($xml);
  return json_decode($json);
}

/**
 * Encode an array into the native configuration format.
 *
 * @param $data
 *   An associative array or an object
 * @return
 *   A representation of this array or object in the native configuration
 *   format. 
 * @todo
 *   This needs to work for objects as well and currently doesn't
 */
function config_encode($data) {
	return config_array_to_xml($data);
}

/**
 * Encode an array or object into the native configuration format.
 *
 * @param $data
 *   An associative array or an object
 * @return
 *   A representation of this array or object in the native configuration
 *   format. 
 */
function config_array_to_xml($arr, $tab_count = 0) {
  foreach ($arr as $tag => $val) { 
    if (!is_array($val)) { 
      $xml .= PHP_EOL . '<' . $tag . '>' . htmlentities($val) . '</' . $tag . '>'; 
    } else { 
      $tab_count++; 
      $xml .= PHP_EOL . '<' . $tag . '>' . config_array_to_xml($val, $tab_count); 
      $xml .= PHP_EOL . '</' . $tag . '>'; 
    } 
  } 
  
	return $xml; 
}

class DrupalConfig {

  /**
   * The storage engine to save this config object to.
   *
   * @var DrupalConfigVerifiedStorageInterface
   */
  protected $_verifiedStorage;

  protected $_overrides;

  public function __construct(DrupalConfigVerifiedStorageInterface $verified_storage, $overrides = array()) {
    $this->_verifiedStorage = $verified_storage;
    $original_keys = (array) config_decode($this->_verifiedStorage->read());
    $this->_overrides = $overrides;
    $active = array_merge($original_keys, $overrides);
    foreach ($active as $key => $value) {
      $this->$key = $value;
    }
  }

  public function isOverridden($key) {
    return isset($this->_overrides[$key]);
  }

  public function save() {
    $obj = new stdClass();
    foreach (get_object_vars($this) as $key => $val) {
      if ($key[0] != '_') {
        $obj->$key = $val;
      }
    }

    $this->_verifiedStorage->write(config_encode($obj));
  }
}
