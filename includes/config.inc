<?php

interface DrupalContextVerifiedStorageInterface {
  function __construct($name);

  function read();

  function syncToFile();

  function syncFromFile();

  function isOutOfSync();

  function writeToActive($content);

  function write($content);

}

class DrupalVerifiedStorageSQL {

  function __construct($name) {
    $this->name = $name;
  }

  protected function signedFileStorage() {
    return new SignedFileStorage($this->name);
  }

  function read() {
    return db_query('SELECT data FROM {config} WHERE name = :name', array(':name' => $name))->fetchField();
  }

  function syncTofile() {
    return $this->signedFileStorage()->write($content);
  }

  function syncFromFile() {
    return $this->writeToActive($this->readFromFile());
  }

  function readFromFile() {
    return $this->signedFileStorage()->read($name);
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

}
