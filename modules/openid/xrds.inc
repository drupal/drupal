<?php

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

  // Since DOCTYPE declarations from an untrusted source could be malicious, we
  // stop parsing here and treat the XML as invalid. XRDS documents do not
  // require, and are not expected to have, a DOCTYPE.
  if (preg_match('/<!DOCTYPE/i', $xml)) {
    return array();
  }

  // Also stop parsing if there is an unreasonably large number of tags.
  // substr_count() has much better performance (compared to preg_match_all())
  // for large payloads but is less accurate, so we check for twice the desired
  // number of allowed tags (to take into account opening/closing tags as well
  // as false positives).
  if (substr_count($xml, '<') > 2 * variable_get('openid_xrds_maximum_tag_count', 30000)) {
    return array();
  }

  xml_parse($parser, $xml);
  xml_parser_free($parser);

  return $xrds_services;
}

/**
 * Parser callback functions
 */
function _xrds_element_start(&$parser, $name, $attribs) {
  global $xrds_open_elements;

  $xrds_open_elements[] = _xrds_strip_namespace($name);
}

function _xrds_element_end(&$parser, $name) {
  global $xrds_open_elements, $xrds_services, $xrds_current_service;

  $name = _xrds_strip_namespace($name);
  if ($name == 'SERVICE') {
    if (in_array(OPENID_NS_2_0 .'/signon', $xrds_current_service['types']) ||
        in_array(OPENID_NS_2_0 .'/server', $xrds_current_service['types'])) {
      $xrds_current_service['version'] = 2;
    }
    elseif (in_array(OPENID_NS_1_1, $xrds_current_service['types']) ||
            in_array(OPENID_NS_1_0, $xrds_current_service['types'])) {
      $xrds_current_service['version'] = 1;
    }
    if (!empty($xrds_current_service['version'])) {
      $xrds_services[] = $xrds_current_service;
    }
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