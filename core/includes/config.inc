<?php

use Drupal\Core\Config\DrupalVerifiedStorageSQL;

/**
 * @file
 * This is the API for configuration storage.
 */

/**
 * Gets the randomly generated config directory name.
 *
 * @return
 *   The directory name.
 */
function config_get_config_directory() {
  global $config_directory_name;

  if ($test_prefix = drupal_valid_test_ua()) {
    $path = conf_path() . '/files/simpletest/config_' . $test_prefix;
  }
  else {
    $path = conf_path() . '/files/' . $config_directory_name;
  }
  return $path;
}

/**
 * Moves the default config supplied by a module to the live config directory.
 *
 * @param
 *   The name of the module we are installing.
 */
function config_install_default_config($module) {
  $module_config_dir = drupal_get_path('module', $module) . '/config';
  $drupal_config_dir = config_get_config_directory();
  if (is_dir(drupal_get_path('module', $module) . '/config')) {
    $files = glob($module_config_dir . '/' . '*.xml');
    foreach ($files as $key => $file) {
      // Load config data into the active store and write it out to the
      // file system in the drupal config directory. Note the config name
      // needs to be the same as the file name WITHOUT the extension.
      // @todo Make this acknowledge other storage engines rather than having
      //   SQL be hardcoded.
      $parts = explode('/', $file);
      $file = array_pop($parts);
      $config_name = str_replace('.xml', '', $file);

      $verified_storage = new DrupalVerifiedStorageSQL($config_name);
      $verified_storage->write(file_get_contents($module_config_dir . '/' . $file));
    }
  }
}

/**
 * Retrieves an iterable array which lists the children under a config 'branch'.
 *
 * Given the following configuration files:
 * - core.entity.node_type.article.xml
 * - core.entity.node_type.page.xml
 *
 * You can pass a prefix 'core.entity.node_type' and get back an array of the
 * filenames that match. This allows you to iterate through all files in a
 * branch. Note that this will only work on the level above the tips, so
 * a prefix of 'core.entity' would return an empty array.
 *
 * @param $prefix
 *   The prefix of the files we are searching for.
 *
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
 * Generates a hash of a config file's contents using our encryption key.
 *
 * @param $data
 *   The contents of a configuration file.
 *
 * @return
 *   A hash of the data.
 */
function config_sign_data($data) {
  // The configuration key is loaded from settings.php and imported into the global namespace
  global $config_signature_key;

  // SHA-512 is both secure and very fast on 64 bit CPUs.
  // @todo What about 32-bit CPUs?
  return hash_hmac('sha512', $data, $config_signature_key);
}

/**
 * @todo
 *
 * @param $prefix
 *   @todo
 *
 * @return
 *   @todo
 */
function config_get_verified_storage_names_with_prefix($prefix = '') {
  return DrupalVerifiedStorageSQL::getNamesWithPrefix($prefix);
}

/**
 * @todo
 *
 * @param $prefix
 *   @todo
 *
 * @return
 *   @todo
 */
function config_get_names_with_prefix($prefix) {
  return config_get_verified_storage_names_with_prefix($prefix);
}

/**
 * Retrieves a configuration object.
 *
 * This is the main entry point to the configuration API. Calling
 * @code config(book.admin) @endcode will return a configuration object in which
 * the book module can store its administrative settings.
 *
 * @param $name
 *   The name of the configuration object to retrieve. The name corresponds to
 *   an XML configuration file. For @code config(book.admin) @endcode, the
 *   config object returned will contain the contents of book.admin.xml.
 * @param $class
 *   The class name of the config object to be returned. Defaults to
 *   DrupalConfig.
 *
 * @return
 *   An instance of the class specified in the $class parameter.
 *
 */
function config($name, $class = 'Drupal\Core\Config\DrupalConfig') {
  // @todo Replace this with an appropriate factory.
  return new $class(new DrupalVerifiedStorageSQL($name));
}

/**
 * Decodes configuration data from its native format to an associative array.
 *
 * @param $data
 *   Configuration data.
 *
 * @return
 *   An associative array representation of the data.
 */
function config_decode($data) {
  if (empty($data)) {
    return array();
  }
  $xml = new SimpleXMLElement($data);
  $json = json_encode($xml);
  return json_decode($json, TRUE);
}

/**
 * Standardizes SimpleXML object output into simple arrays for easier use.
 *
 * @param $xmlObject
 *   A valid XML string.
 *
 * @return
 *   An array representation of A SimpleXML object.
 */
function config_xml_to_array($data) {
  $out = array();
  $xmlObject = simplexml_load_string($data);

  if (is_object($xmlObject)) {
    $attributes = (array) $xmlObject->attributes();
    if (isset($attributes['@attributes'])) {
      $out['#attributes'] = $attributes['@attributes'];
    }
  }
  if (trim((string) $xmlObject)) {
    return trim((string) $xmlObject);
  }
  foreach ($xmlObject as $index => $content) {
    if (is_object($content)) {
      $out[$index] = config_xml_to_array($content);
    }
  }

  return $out;
}

/**
 * Encodes an array into the native configuration format.
 *
 * @param $data
 *   An associative array or an object
 *
 * @return
 *   A representation of this array or object in the native configuration
 *   format.
 *
 * @todo This needs to work for objects as well and currently doesn't.
 */
function config_encode($data) {
  // creating object of SimpleXMLElement
  $xml_object = new SimpleXMLElement("<?xml version=\"1.0\"?><config></config>");

  // function call to convert array to xml
  config_array_to_xml($data, $xml_object);

  // Pretty print the result
  $dom = new DOMDocument('1.0');
  $dom->preserveWhiteSpace = false;
  $dom->formatOutput = true;
  $dom->loadXML($xml_object->asXML());
  // @todo The loaded XML can be invalid; throwing plenty of PHP warnings but no
  //   catchable error.
  return $dom->saveXML();
}

/**
 * Encodes an array into XML
 *
 * @param $data
 *   An associative array or an object
 *
 * @return
 *   A representation of this array or object in the native configuration
 *   format.
 *
 * @todo This needs to work for objects as well and currently doesn't.
 */
function config_array_to_xml($array, &$xml_object) {
  foreach ($array as $key => $value) {
    if (is_array($value)) {
      if (!is_numeric($key)){
        $subnode = $xml_object->addChild("$key");
        config_array_to_xml($value, $subnode);
      }
      else {
        config_array_to_xml($value, $xml_object);
      }
    }
    else {
      // @todo Cast to string must happen in DrupalConfig::set() already. But
      //   over there, $value may also be an array, so the result is "Array".
      $xml_object->addChild($key, (string) $value);
    }
  }
}
