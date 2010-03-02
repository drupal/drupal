<?php
// $Id: xrds.inc,v 1.6 2010/03/02 08:59:54 dries Exp $

// Global variables to track parsing state
$xrds_open_elements = array();
$xrds_services = array();
$xrds_current_service = array();

/**
 * Main entry point for parsing XRDS documents
 */
function xrds_parse($xml) {
  global $xrds_services;

  $parser = xml_parser_create_ns();
  xml_set_element_handler($parser, '_xrds_element_start', '_xrds_element_end');
  xml_set_character_data_handler($parser, '_xrds_cdata');

  xml_parse($parser, $xml);
  xml_parser_free($parser);

  return $xrds_services;
}

/**
 * Parser callback functions
 */
function _xrds_element_start(&$parser, $name, $attributes) {
  global $xrds_open_elements, $xrds_current_service;

  $xrds_open_elements[] = _xrds_strip_namespace($name);

  $path = strtoupper(implode('/', $xrds_open_elements));
  if ($path == 'XRDS/XRD/SERVICE') {
    foreach ($attributes as $attribute_name => $value) {
      if (_xrds_strip_namespace($attribute_name) == 'PRIORITY') {
        $xrds_current_service['priority'] = intval($value);
      }
    }
  }
}

function _xrds_element_end(&$parser, $name) {
  global $xrds_open_elements, $xrds_services, $xrds_current_service;

  $name = _xrds_strip_namespace($name);
  if ($name == 'SERVICE') {
    if (!isset($xrds_current_service['priority'])) {
      // If the priority attribute is absent, the default is infinity.
      $xrds_current_service['priority'] = PHP_INT_MAX;
    }
    $xrds_services[] = $xrds_current_service;
    $xrds_current_service = array();
  }
  array_pop($xrds_open_elements);
}

function _xrds_cdata(&$parser, $data) {
  global $xrds_open_elements, $xrds_services, $xrds_current_service;
  $path = strtoupper(implode('/', $xrds_open_elements));
  switch ($path) {
    case 'XRDS/XRD/SERVICE/TYPE':
      $xrds_current_service['types'][] = $data;
      break;
    case 'XRDS/XRD/SERVICE/URI':
      $xrds_current_service['uri'] = $data;
      break;
    case 'XRDS/XRD/SERVICE/DELEGATE':
      $xrds_current_service['delegate'] = $data;
      break;
    case 'XRDS/XRD/SERVICE/LOCALID':
      $xrds_current_service['localid'] = $data;
      break;
    default:
      if (preg_match('@^XRDS/XRD/SERVICE/(.*)$@', $path, $matches)) {
        $xrds_current_service['additional'][$matches[1]] = $data;
      }
      break;
  }
}

function _xrds_strip_namespace($name) {
  // Strip namespacing.
  $pos = strrpos($name, ':');
  if ($pos !== FALSE) {
    $name = substr($name, $pos + 1, strlen($name));
  }

  return $name;
}
