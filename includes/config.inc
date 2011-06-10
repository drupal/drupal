<?php

function get_signed_file_storage_names_with_prefix($prefix = '') {
  $files = glob(conf_path() . '/config/' . $prefix . '*.php');
  $clean_name = function ($value) { return basename($value, '.inc'); };
  return array_map($clean_name, $files);
}

function config_write_signed_file_storage_key() {
  $key = uniqid() . uniqid() . uniqid();
  if (!file_put_contents(conf_path() . '/config/key.php', '<?php die(); ' . $key . "\n")) {
    throw new Exception('Failed to write configuration signing key.');
  }
}

function config_sign_data($data) {
  $parts = explode(' ', file_get_contents(conf_path() . '/config/key.php'));
  $key = $parts[3];
  // TODO: Consider an HMAC-SHA1 to be more secure?
  return sha1($data . '|' . $key);
}

class SignedFileStorage {
  public function __construct($name) {
    $this->name = $name;
  }

  protected function readAndSplit() {
    // TODO: Optimize with explicit offsets?
    $content = file_get_contents($this->getPath());
    $parts = explode("\n", $content, 2);
    $header_parts = explode(' ', $parts[0]);
    $split = array('data' => $parts[1], 'signature' => $header_parts[2]);
    return $split;
  }

  public function getPath() {
    return conf_path() . '/config/' . $this->name . '.php';
  }

  public function getName() {
    return $this->name;
  }

  public function resign() {
    $parts = $this->readAndSplit();
    $this->write($parts['data']);
  }

  public function verify($contentOnSuccess = FALSE) {
    $split = $this->readAndSplit();
    $expected_signature = config_sign_data($split['data']);
    if ($expected_signature == $split['signature']) {
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
    if (!file_put_contents($this->getPath(), $content)) {
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

interface DrupalVerifiedStorageInterface {
  function __construct($name);

  function read();

  function syncToFile();

  function syncFromFile();

  function isOutOfSync();

  function writeToActive($content);

  function write($content);

  static function getNamesWithPrefix($prefix);
}

class DrupalVerifiedStorageSQL implements DrupalVerifiedStorageInterface {

  function __construct($name) {
    $this->name = $name;
  }

  protected function signedFileStorage() {
    return new SignedFileStorage($this->name);
  }

  function read() {
    return db_query('SELECT data FROM {config} WHERE name = :name', array(':name' => $this->name))->fetchField();
  }

  function syncTofile() {
    return $this->signedFileStorage()->write($content);
  }

  function syncFromFile() {
    return $this->writeToActive($this->readFromFile());
  }

  function readFromFile() {
    return $this->signedFileStorage()->read($this->name);
  }

  function isOutOfSync() {
    return $this->read() !== $this->readFromFile();
  }

  function writeToActive($content) {
    return db_merge('config')
      ->key(array('name' => $this->name))
      ->fields(array('data' => $content))
      ->execute();
  }

  function write($content) {
    $this->writeToActive($content);
    $this->syncToFile();
  }

  static function getNamesWithPrefix($prefix) {
    return db_query('SELECT name FROM {config} WHERE name LIKE :name', array(':name' => db_like($prefix) . '%'))->fetchCol();
  }
}

function get_verified_storage_names_with_prefix($prefix = '') {
  return DrupalVerifiedStorageSQL::getNamesWithPrefix($prefix);
}

