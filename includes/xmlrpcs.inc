<?php
// $Id: xmlrpcs.inc,v 1.37 2010/01/28 07:06:57 webchick Exp $

/**
 * @file
 * Provides API for defining and handling XML-RPC requests.
 */

/**
 * The main entry point for XML-RPC requests.
 *
 * @param $callbacks
 *   Array of external XML-RPC method names with the callbacks they map to.
 */
function xmlrpc_server($callbacks) {
  $xmlrpc_server = new stdClass();
  // Define built-in XML-RPC method names
  $defaults = array(
      'system.multicall' => 'xmlrpc_server_multicall',
    array(
      'system.methodSignature',
      'xmlrpc_server_method_signature',
      array('array', 'string'),
      'Returns an array describing the return type and required parameters of a method.'
    ),
    array(
      'system.getCapabilities',
      'xmlrpc_server_get_capabilities',
      array('struct'),
      'Returns a struct describing the XML-RPC specifications supported by this server.'
    ),
    array(
      'system.listMethods',
      'xmlrpc_server_list_methods',
      array('array'),
      'Returns an array of available methods on this server.'),
    array(
      'system.methodHelp',
      'xmlrpc_server_method_help',
      array('string', 'string'),
      'Returns a documentation string for the specified method.')
  );
  // We build an array of all method names by combining the built-ins
  // with those defined by modules implementing the _xmlrpc hook.
  // Built-in methods are overridable.
  foreach (array_merge($defaults, (array) $callbacks) as $key => $callback) {
    // we could check for is_array($callback)
    if (is_int($key)) {
      $method = $callback[0];
      $xmlrpc_server->callbacks[$method] = $callback[1];
      $xmlrpc_server->signatures[$method] = $callback[2];
      $xmlrpc_server->help[$method] = $callback[3];
    }
    else {
      $xmlrpc_server->callbacks[$key] = $callback;
      $xmlrpc_server->signatures[$key] = '';
      $xmlrpc_server->help[$key] = '';
    }
  }

  $data = file_get_contents('php://input');
  if (!$data) {
    exit('XML-RPC server accepts POST requests only.');
  }
  $xmlrpc_server->message = xmlrpc_message($data);
  if (!xmlrpc_message_parse($xmlrpc_server->message)) {
    xmlrpc_server_error(-32700, t('Parse error. Request not well formed.'));
  }
  if ($xmlrpc_server->message->messagetype != 'methodCall') {
    xmlrpc_server_error(-32600, t('Server error. Invalid XML-RPC. Request must be a methodCall.'));
  }
  if (!isset($xmlrpc_server->message->params)) {
    $xmlrpc_server->message->params = array();
  }
  xmlrpc_server_set($xmlrpc_server);
  $result = xmlrpc_server_call($xmlrpc_server, $xmlrpc_server->message->methodname, $xmlrpc_server->message->params);

  if ($result->is_error) {
    xmlrpc_server_error($result);
  }
  // Encode the result
  $r = xmlrpc_value($result);
  // Create the XML
  $xml = '
<methodResponse>
  <params>
  <param>
    <value>' .
    xmlrpc_value_get_xml($r)
    . '</value>
  </param>
  </params>
</methodResponse>

';
  // Send it
  xmlrpc_server_output($xml);
}

/**
 * Throw an XML-RPC error.
 *
 * @param $error
 *   an error object OR integer error code
 * @param $message
 *   description of error, used only if integer error code was passed
 */
function xmlrpc_server_error($error, $message = FALSE) {
  if ($message && !is_object($error)) {
    $error = xmlrpc_error($error, $message);
  }
  xmlrpc_server_output(xmlrpc_error_get_xml($error));
}

function xmlrpc_server_output($xml) {
  $xml = '<?xml version="1.0"?>' . "\n" . $xml;
  drupal_add_http_header('Content-Length', strlen($xml));
  drupal_add_http_header('Content-Type', 'text/xml');
  echo $xml;
  drupal_exit();
}

/**
 * Store a copy of the request temporarily.
 *
 * @param $xmlrpc_server
 *   Request object created by xmlrpc_server().
 */
function xmlrpc_server_set($xmlrpc_server = NULL) {
  static $server;
  if (!isset($server)) {
    $server = $xmlrpc_server;
  }
  return $server;
}

// Retrieve the stored request.
function xmlrpc_server_get() {
  return xmlrpc_server_set();
}

/**
 * Dispatch the request and any parameters to the appropriate handler.
 *
 * @param $xmlrpc_server
 *   Contains information about this XML-RPC server, the methods it provides,
 *   their signatures, etc.
 * @param $methodname
 *   The external XML-RPC method name, e.g. 'system.methodHelp'
 * @param $args
 *   Array containing any parameters that were sent along with the request.
 */
function xmlrpc_server_call($xmlrpc_server, $methodname, $args) {
  // Make sure parameters are in an array
  if ($args && !is_array($args)) {
    $args = array($args);
  }
  // Has this method been mapped to a Drupal function by us or by modules?
  if (!isset($xmlrpc_server->callbacks[$methodname])) {
    return xmlrpc_error(-32601, t('Server error. Requested method @methodname not specified.', array("@methodname" => $xmlrpc_server->message->methodname)));
  }
  $method = $xmlrpc_server->callbacks[$methodname];
  $signature = $xmlrpc_server->signatures[$methodname];

  // If the method has a signature, validate the request against the signature
  if (is_array($signature)) {
    $ok = TRUE;
    $return_type = array_shift($signature);
    // Check the number of arguments
    if (count($args) != count($signature)) {
      return xmlrpc_error(-32602, t('Server error. Wrong number of method parameters.'));
    }
    // Check the argument types
    foreach ($signature as $key => $type) {
      $arg = $args[$key];
      switch ($type) {
        case 'int':
        case 'i4':
          if (is_array($arg) || !is_int($arg)) {
            $ok = FALSE;
          }
          break;
        case 'base64':
        case 'string':
          if (!is_string($arg)) {
            $ok = FALSE;
          }
          break;
        case 'boolean':
          if ($arg !== FALSE && $arg !== TRUE) {
            $ok = FALSE;
          }
          break;
        case 'float':
        case 'double':
          if (!is_float($arg)) {
            $ok = FALSE;
          }
          break;
        case 'date':
        case 'dateTime.iso8601':
          if (!$arg->is_date) {
            $ok = FALSE;
          }
          break;
      }
      if (!$ok) {
        return xmlrpc_error(-32602, t('Server error. Invalid method parameters.'));
      }
    }
  }

  if (!function_exists($method)) {
    return xmlrpc_error(-32601, t('Server error. Requested function @method does not exist.', array("@method" => $method)));
  }
  // Call the mapped function
  return call_user_func_array($method, $args);
}

function xmlrpc_server_multicall($methodcalls) {
  // See http://www.xmlrpc.com/discuss/msgReader$1208
  $return = array();
  $xmlrpc_server = xmlrpc_server_get();
  foreach ($methodcalls as $call) {
    $ok = TRUE;
    if (!isset($call['methodName']) || !isset($call['params'])) {
      $result = xmlrpc_error(3, t('Invalid syntax for system.multicall.'));
      $ok = FALSE;
    }
    $method = $call['methodName'];
    $params = $call['params'];
    if ($method == 'system.multicall') {
      $result = xmlrpc_error(-32600, t('Recursive calls to system.multicall are forbidden.'));
    }
    elseif ($ok) {
      $result = xmlrpc_server_call($xmlrpc_server, $method, $params);
    }
    if ($result->is_error) {
      $return[] = array(
        'faultCode' => $result->code,
        'faultString' => $result->message
      );
    }
    else {
      $return[] = array($result);
    }
  }
  return $return;
}


/**
 * XML-RPC method system.listMethods maps to this function.
 */
function xmlrpc_server_list_methods() {
  $xmlrpc_server = xmlrpc_server_get();
  return array_keys($xmlrpc_server->callbacks);
}

/**
 * XML-RPC method system.getCapabilities maps to this function.
 *
 * @see http://groups.yahoo.com/group/xml-rpc/message/2897
 */
function xmlrpc_server_get_capabilities() {
  return array(
    'xmlrpc' => array(
      'specUrl' => 'http://www.xmlrpc.com/spec',
      'specVersion' => 1
    ),
    'faults_interop' => array(
      'specUrl' => 'http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php',
      'specVersion' => 20010516
    ),
    'system.multicall' => array(
      'specUrl' => 'http://www.xmlrpc.com/discuss/msgReader$1208',
      'specVersion' => 1
    ),
    'introspection' => array(
    'specUrl' => 'http://scripts.incutio.com/xmlrpc/introspection.html',
    'specVersion' => 1
    )
  );
}

/**
 * XML-RPC method system.methodSignature maps to this function.
 *
 * @param $methodname
 *   Name of method for which we return a method signature.
 * @return
 *   An array of types representing the method signature of the
 *   function that the methodname maps to. The methodSignature of
 *   this function is 'array', 'string' because it takes an array
 *   and returns a string.
 */
function xmlrpc_server_method_signature($methodname) {
  $xmlrpc_server = xmlrpc_server_get();
  if (!isset($xmlrpc_server->callbacks[$methodname])) {
    return xmlrpc_error(-32601, t('Server error. Requested method @methodname not specified.', array("@methodname" => $methodname)));
  }
  if (!is_array($xmlrpc_server->signatures[$methodname])) {
    return xmlrpc_error(-32601, t('Server error. Requested method @methodname signature not specified.', array("@methodname" => $methodname)));
  }
  // We array of types
  $return = array();
  foreach ($xmlrpc_server->signatures[$methodname] as $type) {
    $return[] = $type;
  }
  return $return;
}

/**
 * XML-RPC method system.methodHelp maps to this function.
 *
 * @param $method
 *   Name of method for which we return a help string.
 */
function xmlrpc_server_method_help($method) {
  $xmlrpc_server = xmlrpc_server_get();
  return $xmlrpc_server->help[$method];
}
