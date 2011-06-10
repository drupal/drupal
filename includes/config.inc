<?php

interface DrupalContextVerifiedStorageInterface {
  function __construct($name);

  function read();

  function syncToFile();

  function syncFromFile();

  function isOutOfSync();

  function writeToActive($content);

  function write($content);

  static function getNamesWithPrefix($prefix);
}

class DrupalVerifiedStorageSQL {

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
