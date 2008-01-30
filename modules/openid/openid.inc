<?php
// $Id: openid.inc,v 1.8 2008/01/30 22:11:22 goba Exp $

/**
 * @file
 * OpenID utility functions.
 */

// Diffie-Hellman Key Exchange Default Value.
define('OPENID_DH_DEFAULT_MOD', '155172898181473697471232257763715539915724801'.
       '966915404479707795314057629378541917580651227423698188993727816152646631'.
       '438561595825688188889951272158842675419950341258706556549803580104870537'.
       '681476726513255747040765857479291291572334510643245094715007229621094194'.
       '349783925984760375594985848253359305585439638443');

// Constants for Diffie-Hellman key exchange computations.
define('OPENID_DH_DEFAULT_GEN', '2');
define('OPENID_SHA1_BLOCKSIZE', 64);
define('OPENID_RAND_SOURCE', '/dev/urandom');

// OpenID namespace URLs
define('OPENID_NS_2_0', 'http://specs.openid.net/auth/2.0');
define('OPENID_NS_1_1', 'http://openid.net/signon/1.1');
define('OPENID_NS_1_0', 'http://openid.net/signon/1.0');

/**
 * Performs an HTTP 302 redirect (for the 1.x protocol).
 */
function openid_redirect_http($url, $message) {
  $query = array();
  foreach ($message as $key => $val) {
    $query[] = $key .'='. urlencode($val);
  }

  $sep = (strpos($url, '?') === FALSE) ? '?' : '&';
  header('Location: '. $url . $sep . implode('&', $query), TRUE, 302);
  exit;
}

/**
 * Creates a js auto-submit redirect for (for the 2.x protocol)
 */
function openid_redirect($url, $message) {
  $output = '<html><head><title>'. t('OpenID redirect') ."</title></head>\n<body>";
  $output .= drupal_get_form('openid_redirect_form', $url, $message);
  $output .= '<script type="text/javascript">document.getElementById("openid-redirect-form").submit();</script>';
  $output .= "</body></html>\n";
  print $output;
  exit;
}

function openid_redirect_form(&$form_state, $url, $message) {
  $form = array();
  $form['#action'] = $url;
  $form['#method'] = "post";
  foreach ($message as $key => $value) {
    $form[$key] = array(
      '#type' => 'hidden',
      '#name' => $key,
      '#value' => $value,
    );
  }
  $form['submit'] = array(
    '#type' => 'submit',
    '#prefix' => '<noscript>',
    '#suffix' => '</noscript>',
    '#value' => t('Send'),
  );

  return $form;
}

/**
 * Determine if the given identifier is an XRI ID.
 */
function _openid_is_xri($identifier) {
  $firstchar = substr($identifier, 0, 1);
  if ($firstchar == "@" || $firstchar == "=")
    return TRUE;

  if (stristr($identifier, 'xri://') !== FALSE) {
    return TRUE;
  }

  return FALSE;
}

/**
 * Normalize the given identifier as per spec.
 */
function _openid_normalize($identifier) {
  if (_openid_is_xri($identifier)) {
    return _openid_normalize_xri($identifier);
  }
  else {
    return _openid_normalize_url($identifier);
  }
}

function _openid_normalize_xri($xri) {
  $normalized_xri = $xri;
  if (stristr($xri, 'xri://') !== FALSE) {
    $normalized_xri = substr($xri, 6);
  }
  return $normalized_xri;
}

function _openid_normalize_url($url) {
  $normalized_url = $url;

  if (stristr($url, '://') === FALSE) {
    $normalized_url = 'http://'. $url;
  }

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
      return null;
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
      $encoded_message .= rawurlencode(trim($parts[0])) .'='. rawurlencode(trim($parts[1]));
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
  preg_match('|<link\s+rel=["\'](.*)'. $rel .'(.*)["\'](.*)/?>|iUs', $html, $matches);
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
  preg_match('|<meta\s+http-equiv=["\']'. $equiv .'["\'](.*)/?>|iUs', $html, $matches);
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
    if (isset($message_array['openid.'. $key])) {
      $sign_data[$key] = $message_array['openid.'. $key];
    }
  }

  $message = _openid_create_message($sign_data);
  $secret = base64_decode($association->mac_key);
  $signature = _openid_hmac($secret, $message);

  return base64_encode($signature);
}

function _openid_hmac($key, $text) {
  if (strlen($key) > OPENID_SHA1_BLOCKSIZE) {
    $key = _openid_sha1($key, true);
  }

  $key = str_pad($key, OPENID_SHA1_BLOCKSIZE, chr(0x00));
  $ipad = str_repeat(chr(0x36), OPENID_SHA1_BLOCKSIZE);
  $opad = str_repeat(chr(0x5c), OPENID_SHA1_BLOCKSIZE);
  $hash1 = _openid_sha1(($key ^ $ipad) . $text, true);
  $hmac = _openid_sha1(($key ^ $opad) . $hash1, true);

  return $hmac;
}

function _openid_sha1($text) {
  $hex = sha1($text);
  $raw = '';
  for ($i = 0; $i < 40; $i += 2) {
    $hexcode = substr($hex, $i, 2);
    $charcode = (int)base_convert($hexcode, 16, 10);
    $raw .= chr($charcode);
  }
  return $raw;
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
  $sha1_dh_shared = _openid_sha1($dh_shared_str);
  $xsecret = "";
  for ($i = 0; $i < strlen($secret); $i++) {
    $xsecret .= chr(ord($secret[$i]) ^ ord($sha1_dh_shared[$i]));
  }

  return $xsecret;
}

function _openid_dh_rand($stop) {
  static $duplicate_cache = array();

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
    $bytes = "\x00". _openid_get_bytes($nbytes);
    $n = _openid_dh_binary_to_long($bytes);
    // Keep looping if this value is in the low duplicated range.
  } while (bccomp($n, $duplicate) < 0);

  return bcmod($n, $stop);
}

function _openid_get_bytes($num_bytes) {
  static $f = null;
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
      if ($str !== false) {
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
 * Provide bcpowmod support for PHP4.
 */
if (!function_exists('bcpowmod')) {
  function bcpowmod($base, $exp, $mod) {
    $square = bcmod($base, $mod);
    $result = 1;
    while (bccomp($exp, 0) > 0) {
      if (bcmod($exp, 2)) {
        $result = bcmod(bcmul($result, $square), $mod);
      }
      $square = bcmod(bcmul($square, $square), $mod);
      $exp = bcdiv($exp, 2);
    }
    return $result;
  }
}
