<?php
// $Id: openid.inc,v 1.33 2010/05/13 17:37:24 dries Exp $

/**
 * @file
 * OpenID utility functions.
 */

/**
 * Diffie-Hellman Key Exchange Default Value.
 *
 * This is used to establish an association between the Relying Party and the
 * OpenID Provider.
 *
 * See RFC 2631: http://www.ietf.org/rfc/rfc2631.txt
 */
define('OPENID_DH_DEFAULT_MOD', '155172898181473697471232257763715539915724801' .
       '966915404479707795314057629378541917580651227423698188993727816152646631' .
       '438561595825688188889951272158842675419950341258706556549803580104870537' .
       '681476726513255747040765857479291291572334510643245094715007229621094194' .
       '349783925984760375594985848253359305585439638443');

/**
 * Diffie-Hellman generator; used for Diffie-Hellman key exchange computations.
 */
define('OPENID_DH_DEFAULT_GEN', '2');

/**
 * SHA-1 hash block size; used for Diffie-Hellman key exchange computations.
 */
define('OPENID_SHA1_BLOCKSIZE', 64);

/**
 * Random number generator; used for Diffie-Hellman key exchange computations.
 */
define('OPENID_RAND_SOURCE', '/dev/urandom');

/**
 * OpenID Authentication 2.0 namespace URL.
 */
define('OPENID_NS_2_0', 'http://specs.openid.net/auth/2.0');

/**
 * OpenID Authentication 1.1 namespace URL; used for backwards-compatibility.
 */
define('OPENID_NS_1_1', 'http://openid.net/signon/1.1');

/**
 * OpenID Authentication 1.0 namespace URL; used for backwards-compatibility.
 */
define('OPENID_NS_1_0', 'http://openid.net/signon/1.0');

/**
 * OpenID Simple Registration extension.
 */
define('OPENID_NS_SREG', 'http://openid.net/extensions/sreg/1.1');

/**
 * OpenID Attribute Exchange extension.
 */
define('OPENID_NS_AX', 'http://openid.net/srv/ax/1.0');

/**
 * Extensible Resource Descriptor documents.
 */
define('OPENID_NS_XRD', 'xri://$xrd*($v*2.0)');

/**
 * Performs an HTTP 302 redirect (for the 1.x protocol).
 */
function openid_redirect_http($url, $message) {
  $query = array();
  foreach ($message as $key => $val) {
    $query[] = $key . '=' . urlencode($val);
  }

  $sep = (strpos($url, '?') === FALSE) ? '?' : '&';
  header('Location: ' . $url . $sep . implode('&', $query), TRUE, 302);

  drupal_exit();
}

/**
 * Creates a js auto-submit redirect for (for the 2.x protocol)
 */
function openid_redirect($url, $message) {
  global $language;
  
  $output = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\n";
  $output .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . $language->language . '" lang="' . $language->language . '">' . "\n";
  $output .= "<head>\n";
  $output .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
  $output .= "<title>" . t('OpenID redirect') . "</title>\n";
  $output .= "</head>\n";
  $output .= "<body>\n";
  $elements = drupal_get_form('openid_redirect_form', $url, $message);
  $output .= drupal_render($elements);
  $output .= '<script type="text/javascript">document.getElementById("openid-redirect-form").submit();</script>' . "\n";
  $output .= "</body>\n";
  $output .= "</html>\n";
  print $output;

  drupal_exit();
}

function openid_redirect_form($form, &$form_state, $url, $message) {
  $form['#action'] = $url;
  $form['#method'] = "post";
  foreach ($message as $key => $value) {
    $form[$key] = array(
      '#type' => 'hidden',
      '#name' => $key,
      '#value' => $value,
    );
  }
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#prefix' => '<noscript><div>',
    '#suffix' => '</div></noscript>',
    '#value' => t('Send'),
  );

  return $form;
}

/**
 * Parse an XRDS document.
 *
 * @param $raw_xml
 *   A string containing the XRDS document.
 * @return
 *   An array of service entries.
 */
function _openid_xrds_parse($raw_xml) {
  $services = array();
  try {
    $xml = @new SimpleXMLElement($raw_xml);
    foreach ($xml->children(OPENID_NS_XRD)->XRD as $xrd) {
      foreach ($xrd->children(OPENID_NS_XRD)->Service as $service_element) {
        $service = array(
          'priority' => $service_element->attributes()->priority ? (int)$service_element->attributes()->priority : PHP_INT_MAX,
          'types' => array(),
          'uri' => (string)$service_element->children(OPENID_NS_XRD)->URI,
          'service' => $service_element,
          'xrd' => $xrd,
        );
        foreach ($service_element->Type as $type) {
          $service['types'][] = (string)$type;
        }
        if ($service_element->children(OPENID_NS_XRD)->Delegate) {
          $service['identity'] = (string)$service_element->children(OPENID_NS_XRD)->Delegate;
        }
        if ($service_element->children(OPENID_NS_XRD)->LocalID) {
          $service['identity'] = (string)$service_element->children(OPENID_NS_XRD)->LocalID;
        }
        else {
          $service['identity'] = FALSE;
        }
        $services[] = $service;
      }
    }
  }
  catch (Exception $e) {
    // Invalid XML.
  }
  return $services;
}

/**
 * Select a service element.
 *
 * The procedure is described in OpenID Authentication 2.0, section 7.3.2.
 *
 * A new entry is added to the returned array with the key 'version' and the
 * value 1 or 2 specifying the protocol version used by the service.
 *
 * @param $services
 *   An array of service arrays as returned by openid_discovery().
 * @return
 *   The selected service array, or NULL if no valid services were found.
 */
function _openid_select_service(array $services) {
  // Extensible Resource Identifier (XRI) Resolution Version 2.0, section 4.3.3:
  // Find the service with the highest priority (lowest integer value). If there
  // is a tie, select a random one, not just the first in the XML document.
  $selected_service = NULL;
  shuffle($services);

  // Search for an OP Identifier Element.
  foreach ($services as $service) {
    if (!empty($service['uri'])) {
      if (in_array('http://specs.openid.net/auth/2.0/server', $service['types'])) {
        $service['version'] = 2;
      }
      elseif (in_array(OPENID_NS_1_0, $service['types']) || in_array(OPENID_NS_1_1, $service['types'])) {
        $service['version'] = 1;
      }
      if (isset($service['version']) && (!$selected_service || $service['priority'] < $selected_service['priority'])) {
        $selected_service = $service;
      }
    }
  }

  if (!$selected_service) {
    // Search for Claimed Identifier Element.
    foreach ($services as $service) {
      if (!empty($service['uri']) && in_array('http://specs.openid.net/auth/2.0/signon', $service['types'])) {
        $service['version'] = 2;
        if (!$selected_service || $service['priority'] < $selected_service['priority']) {
          $selected_service = $service;
        }
      }
    }
  }

  if ($selected_service) {
    // Unset SimpleXMLElement instances that cannot be saved in $_SESSION.
    unset($selected_service['xrd']);
    unset($selected_service['service']);
  }

  return $selected_service;
}

/**
 * Determine if the given identifier is an XRI ID.
 */
function _openid_is_xri($identifier) {
  // Strip the xri:// scheme from the identifier if present.
  if (stripos($identifier, 'xri://') === 0) {
    $identifier = substr($identifier, 6);
  }

  // Test whether the identifier starts with an XRI global context symbol or (.
  $firstchar = substr($identifier, 0, 1);
  if (strpos("=@+$!(", $firstchar) !== FALSE) {
    return TRUE;
  }

  return FALSE;
}

/**
 * Normalize the given identifier.
 *
 * The procedure is described in OpenID Authentication 2.0, section 7.2.
 */
function openid_normalize($identifier) {
  $methods = module_invoke_all('openid_normalization_method_info');
  drupal_alter('openid_normalization_method_info', $methods);

  // Execute each method in turn, stopping after the first method accepted
  // the identifier.
  foreach ($methods as $method) {
    $result = $method($identifier);
    if ($result !== NULL) {
      $identifier = $result;
      break;
    }
  }

  return $identifier;
}

/**
 * OpenID normalization method: normalize XRI identifiers.
 */
function _openid_xri_normalize($identifier) {
  if (_openid_is_xri($identifier)) {
    if (stristr($identifier, 'xri://') !== FALSE) {
      $identifier = substr($identifier, 6);
    }
    return $identifier;
  }
}

/**
 * OpenID normalization method: normalize URL identifiers.
 */
function _openid_url_normalize($url) {
  $normalized_url = $url;

  if (stristr($url, '://') === FALSE) {
    $normalized_url = 'http://' . $url;
  }

  // Strip the fragment and fragment delimiter if present.
  $normalized_url = strtok($normalized_url, '#');

  if (substr_count($normalized_url, '/') < 3) {
    $normalized_url .= '/';
  }

  return $normalized_url;
}

/**
 * Create a serialized message packet as per spec: $key:$value\n .
 */
function _openid_create_message($data) {
  $serialized = '';

  foreach ($data as $key => $value) {
    if ((strpos($key, ':') !== FALSE) || (strpos($key, "\n") !== FALSE) || (strpos($value, "\n") !== FALSE)) {
      return NULL;
    }
    $serialized .= "$key:$value\n";
  }
  return $serialized;
}

/**
 * Encode a message from _openid_create_message for HTTP Post
 */
function _openid_encode_message($message) {
  $encoded_message = '';

  $items = explode("\n", $message);
  foreach ($items as $item) {
    $parts = explode(':', $item, 2);

    if (count($parts) == 2) {
      if ($encoded_message != '') {
        $encoded_message .= '&';
      }
      $encoded_message .= rawurlencode(trim($parts[0])) . '=' . rawurlencode(trim($parts[1]));
    }
  }

  return $encoded_message;
}

/**
 * Convert a direct communication message
 * into an associative array.
 */
function _openid_parse_message($message) {
  $parsed_message = array();

  $items = explode("\n", $message);
  foreach ($items as $item) {
    $parts = explode(':', $item, 2);

    if (count($parts) == 2) {
      $parsed_message[$parts[0]] = $parts[1];
    }
  }

  return $parsed_message;
}

/**
 * Return a nonce value - formatted per OpenID spec.
 */
function _openid_nonce() {
  // YYYY-MM-DDThh:mm:ssTZD UTC, plus some optional extra unique chars
  return gmstrftime('%Y-%m-%dT%H:%M:%S%Z') .
    chr(mt_rand(0, 25) + 65) .
    chr(mt_rand(0, 25) + 65) .
    chr(mt_rand(0, 25) + 65) .
    chr(mt_rand(0, 25) + 65);
}

/**
 * Pull the href attribute out of an html link element.
 */
function _openid_link_href($rel, $html) {
  $rel = preg_quote($rel);
  preg_match('|<link\s+rel=["\'](.*)' . $rel . '(.*)["\'](.*)/?>|iUs', $html, $matches);
  if (isset($matches[3])) {
    preg_match('|href=["\']([^"]+)["\']|iU', $matches[3], $href);
    return trim($href[1]);
  }
  return FALSE;
}

/**
 * Pull the http-equiv attribute out of an html meta element
 */
function _openid_meta_httpequiv($equiv, $html) {
  preg_match('|<meta\s+http-equiv=["\']' . $equiv . '["\'](.*)/?>|iUs', $html, $matches);
  if (isset($matches[1])) {
    preg_match('|content=["\']([^"]+)["\']|iUs', $matches[1], $content);
    if (isset($content[1])) {
      return $content[1];
    }
  }
  return FALSE;
}

/**
 * Sign certain keys in a message
 * @param $association - object loaded from openid_association or openid_server_association table
 *              - important fields are ->assoc_type and ->mac_key
 * @param $message_array - array of entire message about to be sent
 * @param $keys_to_sign - keys in the message to include in signature (without
 *  'openid.' appended)
 */
function _openid_signature($association, $message_array, $keys_to_sign) {
  $signature = '';
  $sign_data = array();

  foreach ($keys_to_sign as $key) {
    if (isset($message_array['openid.' . $key])) {
      $sign_data[$key] = $message_array['openid.' . $key];
    }
  }

  $message = _openid_create_message($sign_data);
  $secret = base64_decode($association->mac_key);
  $signature = _openid_hmac($secret, $message);

  return base64_encode($signature);
}

function _openid_hmac($key, $text) {
  if (strlen($key) > OPENID_SHA1_BLOCKSIZE) {
    $key = sha1($key, TRUE);
  }

  $key = str_pad($key, OPENID_SHA1_BLOCKSIZE, chr(0x00));
  $ipad = str_repeat(chr(0x36), OPENID_SHA1_BLOCKSIZE);
  $opad = str_repeat(chr(0x5c), OPENID_SHA1_BLOCKSIZE);
  $hash1 = sha1(($key ^ $ipad) . $text, TRUE);
  $hmac = sha1(($key ^ $opad) . $hash1, TRUE);

  return $hmac;
}

function _openid_dh_base64_to_long($str) {
  $b64 = base64_decode($str);

  return _openid_dh_binary_to_long($b64);
}

function _openid_dh_long_to_base64($str) {
  return base64_encode(_openid_dh_long_to_binary($str));
}

function _openid_dh_binary_to_long($str) {
  $bytes = array_merge(unpack('C*', $str));

  $n = 0;
  foreach ($bytes as $byte) {
    $n = bcmul($n, pow(2, 8));
    $n = bcadd($n, $byte);
  }

  return $n;
}

function _openid_dh_long_to_binary($long) {
  $cmp = bccomp($long, 0);
  if ($cmp < 0) {
    return FALSE;
  }

  if ($cmp == 0) {
    return "\x00";
  }

  $bytes = array();

  while (bccomp($long, 0) > 0) {
    array_unshift($bytes, bcmod($long, 256));
    $long = bcdiv($long, pow(2, 8));
  }

  if ($bytes && ($bytes[0] > 127)) {
    array_unshift($bytes, 0);
  }

  $string = '';
  foreach ($bytes as $byte) {
    $string .= pack('C', $byte);
  }

  return $string;
}

function _openid_dh_xorsecret($shared, $secret) {
  $dh_shared_str = _openid_dh_long_to_binary($shared);
  $sha1_dh_shared = sha1($dh_shared_str, TRUE);
  $xsecret = "";
  for ($i = 0; $i < strlen($secret); $i++) {
    $xsecret .= chr(ord($secret[$i]) ^ ord($sha1_dh_shared[$i]));
  }

  return $xsecret;
}

function _openid_dh_rand($stop) {
  $duplicate_cache = &drupal_static(__FUNCTION__, array());

  // Used as the key for the duplicate cache
  $rbytes = _openid_dh_long_to_binary($stop);

  if (array_key_exists($rbytes, $duplicate_cache)) {
    list($duplicate, $nbytes) = $duplicate_cache[$rbytes];
  }
  else {
    if ($rbytes[0] == "\x00") {
      $nbytes = strlen($rbytes) - 1;
    }
    else {
      $nbytes = strlen($rbytes);
    }

    $mxrand = bcpow(256, $nbytes);

    // If we get a number less than this, then it is in the
    // duplicated range.
    $duplicate = bcmod($mxrand, $stop);

    if (count($duplicate_cache) > 10) {
      $duplicate_cache = array();
    }

    $duplicate_cache[$rbytes] = array($duplicate, $nbytes);
  }

  do {
    $bytes = "\x00" . _openid_get_bytes($nbytes);
    $n = _openid_dh_binary_to_long($bytes);
    // Keep looping if this value is in the low duplicated range.
  } while (bccomp($n, $duplicate) < 0);

  return bcmod($n, $stop);
}

function _openid_get_bytes($num_bytes) {
  $f = &drupal_static(__FUNCTION__);
  $bytes = '';
  if (!isset($f)) {
    $f = @fopen(OPENID_RAND_SOURCE, "r");
  }
  if (!$f) {
    // pseudorandom used
    $bytes = '';
    for ($i = 0; $i < $num_bytes; $i += 4) {
      $bytes .= pack('L', mt_rand());
    }
    $bytes = substr($bytes, 0, $num_bytes);
  }
  else {
    $bytes = fread($f, $num_bytes);
  }
  return $bytes;
}

function _openid_response($str = NULL) {
  $data = array();

  if (isset($_SERVER['REQUEST_METHOD'])) {
    $data = _openid_get_params($_SERVER['QUERY_STRING']);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $str = file_get_contents('php://input');

      $post = array();
      if ($str !== FALSE) {
        $post = _openid_get_params($str);
      }

      $data = array_merge($data, $post);
    }
  }

  return $data;
}

function _openid_get_params($str) {
  $chunks = explode("&", $str);

  $data = array();
  foreach ($chunks as $chunk) {
    $parts = explode("=", $chunk, 2);

    if (count($parts) == 2) {
      list($k, $v) = $parts;
      $data[$k] = urldecode($v);
    }
  }
  return $data;
}

/**
 * Extract all the parameters belonging to an extension in a response message.
 *
 * OpenID 2.0 defines a simple extension mechanism, based on a namespace prefix.
 *
 * Each request or response can define a prefix using:
 * @code
 *   openid.ns.[prefix] = [extension_namespace]
 *   openid.[prefix].[key1] = [value1]
 *   openid.[prefix].[key2] = [value2]
 *   ...
 * @endcode
 *
 * This function extracts all the keys belonging to an extension namespace in a
 * response, optionally using a fallback prefix if none is provided in the response.
 *
 * Note that you cannot assume that a given extension namespace will use the same
 * prefix on the response and the request: each party may use a different prefix
 * to refer to the same namespace.
 *
 * @param $response
 *   The response array.
 * @param $extension_namespace
 *   The namespace of the extension.
 * @param $fallback_prefix
 *   An optional prefix that will be used in case no prefix is found for the
 *   target extension namespace.
 * @return
 *   An associative array containing all the parameters in the response message
 *   that belong to the extension. The keys are stripped from their namespace
 *   prefix.
 * @see http://openid.net/specs/openid-authentication-2_0.html#extensions
 */
function openid_extract_namespace($response, $extension_namespace, $fallback_prefix = NULL) {
  // Find the namespace prefix.
  $prefix = $fallback_prefix;
  foreach ($response as $key => $value) {
    if ($value == $extension_namespace && preg_match('/^openid\.ns\.([^.]+)$/', $key, $matches)) {
      $prefix = $matches[1];
      break;
    }
  }

  // Now extract the namespace keys from the response.
  $output = array();
  if (!isset($prefix)) {
    return $output;
  }
  foreach ($response as $key => $value) {
    if (preg_match('/^openid\.' . $prefix . '\.(.+)$/', $key, $matches)) {
      $local_key = $matches[1];
      $output[$local_key] = $value;
    }
  }

  return $output;
}

/**
 * Extracts values from an OpenID AX Response.
 *
 * The values can be returned in two forms:
 *   - only openid.ax.value.<alias> (for single-valued answers)
 *   - both openid.ax.count.<alias> and openid.ax.value.<alias>.<count> (for both
 *     single and multiple-valued answers)
 *
 * @param $values
 *   An array as returned by openid_extract_namespace(..., OPENID_NS_AX).
 * @param $aliases
 *   An array of aliases used in the fetch request.
 * @return
 *   An array of values.
 * @see http://openid.net/specs/openid-attribute-exchange-1_0.html#fetch_response
 */
function openid_extract_ax_values($values, $aliases) {
  $output = array();
  foreach ($aliases as $alias) {
    if (isset($values['count.' . $alias])) {
      for ($i = 1; $i <= $values['count.' . $alias]; $i++) {
        $output[] = $values['value.' . $alias . '.' . $i];
      }
    }
    elseif (isset($values['value.' . $alias])) {
      $output[] = $values['value.' . $alias];
    }
  }
  return $output;
}

