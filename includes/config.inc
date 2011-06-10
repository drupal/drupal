<?php

/**
 * @file
 * Configuration storage and management API.
 */


function get_signed_file_storage_names_with_prefix($type, $prefix = '') {
  $files = glob(conf_path() . '/config/' . $prefix . '*.' . $type . '.php');
  $clean_name = function ($value) {
    return basename($value, '.php');
  };
  return array_map($clean_name, $files);
}

function config_write_signed_file_storage_key($force_rekey = FALSE) {
  $file_path = conf_path() . '/key.php';
  if (!file_exists($file_path) || $force_rekey) {
    $key = uniqid() . uniqid() . uniqid();
    if (!file_put_contents($file_path, '<?php die(); ' . $key . "\n")) {
      throw new Exception('Failed to write configuration signing key.');
    }
  }
}

function config_sign_data($data) {
  $file = file_get_contents(conf_path() . '/key.php');
  if ($file === FALSE) {
    throw new Exception('Key file not found.');
  }
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
   * param @type
   *   The type of the stored data. For example 'json' or 'xml' etc.
   * param @name
   *   Lowercase string, the name for the configuration data.
   */
  public function __construct($type, $name) {
    $this->type = $type;
    $this->name = $name;
  }

  /**
   * Read a signed file and split the content into signature and data.
   *
   * @return
   *   An array with "signature" and "data" keys.
   *
   * @throws
   *   Exception
   */
  protected function readAndSplit() {
    // TODO: Optimize with explicit offsets?
    $content = file_get_contents($this->getFilePath());
    if ($content === FALSE) {
      throw new Exception('Read file is invalid.');
    }
    $parts = explode("\n", $content, 2);
    if (count($parts) !== 2) {
      throw new Exception('Read file does not verify.');
    }
    $header_parts = explode(' ', $parts[0]);
    if (count($header_parts) !== 3) {
      throw new Exception('Read file not valid.');
    }
    return array('data' => $parts[1], 'signature' => $header_parts[2]);
  }

  public function getFilePath() {
    return conf_path() . '/config/' . $this->name  . '.' . $this->type . '.php';
  }

  public function resign() {
    $parts = $this->readAndSplit();
    $this->write($parts['data']);
  }

  public function verify($contentOnSuccess = FALSE) {
    $split = $this->readAndSplit();
    $expected_signature = config_sign_data($split['data']);
    if ($expected_signature === $split['signature']) {
      if ($contentOnSuccess) {
        return $split['data'];
      }
      return TRUE;
    }
    return FALSE;
  }

  public function write($data) {
    $signature = config_sign_data($data);
    $content = '<?php die(); ' . $signature . "\n" . $data;
    if (!file_put_contents($this->getFilePath(), $content)) {
      throw new Exception('Failed to write signed file.');
    }
  }

  public function read() {
    $verification = $this->verify(TRUE);
    if ($verification === FALSE) {
      throw new Exception('Invalid signature in file header.');
    }
    return $verification;
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
   * param @type
   *   The type of the stored data. For example 'json' or 'xml' etc.
   * param @name
   *   Lowercase string, the name for the configuration data.
   */
  function __construct($type, $name);

  /**
   * Read the configuration data from the verified storage, including local overlays.
   */
  function read();

  /**
   * Read the configuration data from the verified storage, excluding local overlays.
   */
  function readFromActive();

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

/**
 * Retrieve any local overrides to configuration.
 *
 * @return
 *   An array mapping configuration bundle names
 *   to keys to replace to the replacement values.
 */
function config_get_local_overrides() {
  static $overrides;
  if (!isset($overrides)) {
    // TODO: Use the appropriate factory.
    $overrides = (array) DrupalVerifiedStorageSQL('local');
  }
  return $overrides;
}

abstract class DrupalConfigVerifiedStorage implements DrupalConfigVerifiedStorageInterface {
  function __construct($type, $name) {
    $this->type = $type;
    $this->name = $name;
  }

  protected function signedFileStorage() {
    return new SignedFileStorage($this->type, $this->name);
  }

  public function read() {
    $overrides = config_get_local_overrides();
    $active = $this->readFromActive();
    if (isset($overrides[$this->name])) {
      return array_merge($active, $overrides[$this->name]);
    }
    return $active;
  }

  public function copyTofile() {
    return $this->signedFileStorage()->write($data);
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
    $this->syncToFile();
  }
}

class DrupalVerifiedStorageSQL extends DrupalConfigVerifiedStorage {

  public function readFromActive() {
    return db_query('SELECT data FROM {config} WHERE type = :type, name = :name', array(':type' => $this->type, ':name' => $this->name))->fetchField();
  }

  public function writeToActive($data) {
    return db_merge('config')
      ->key(array('type' => $this->type, 'name' => $this->name))
      ->fields(array('data' => $data))
      ->execute();
  }

  static public function getNamesWithPrefix($type = '*', $prefix = '') {
    $query = db_select('config');
    $query->addField('name');
    if ($type != '*') {
      $query->condition('type', $type);
    }
    if ($prefix) {
      $query->condition('name', db_like($prefix) . '%', 'LIKE');
    }
    return $query->execute()->fetchCol();
  }
}

function get_verified_storage_names_with_prefix($type = '*', $prefix = '') {
  return DrupalVerifiedStorageSQL::getNamesWithPrefix($type, $prefix);
}

function config_get_names_with_prefix($prefix) {
  return get_verified_storage_names_with_prefix('json', $prefix);
}

function config($name, $class = 'DrupalConfig') {
  return new $class($name);
}

class DrupalConfig {

  protected $_verifiedStorage;

  protected $_name;

  public function __construct($name) {
    $this->_verifiedStorage = new DrupalVerifiedStorageSQL('json', $name);
    foreach (json_decode($this->_verifiedStorage->read()) as $key => $value) {
      $this->$key = $value;
    }
  }

  public function save() {
    $verifiedStorage = $this->_verifiedStorage;
    unset($this->_verifiedStorage);
    $verifiedStorage->write(json_encode(get_object_vars($this)));
  }
}
