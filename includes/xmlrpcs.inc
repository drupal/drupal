<?php

function xmlrpc_server($callbacks) {
  $xmlrpc_server = new stdClass();
  $defaults = array(
    'system.multicall' => 'xmlrpc_server_multicall',
    array(
      'system.methodSignature',
      'xmlrpc_server_method_signature',
      array('array', 'string'),
      'Returns an array describing the return type and required parameters of a method'
    ),
    array(
      'system.getCapabilities',
      'xmlrpc_server_get_capabilities',
      array('struct'),
      'Returns a struct describing the XML-RPC specifications supported by this server'
    ),
    array(
      'system.listMethods',
      'xmlrpc_server_list_methods',
      array('array'),
      'Returns an array of available methods on this server'),
    array(
      'system.methodHelp',
      'xmlrpc_server_method_help',
      array('string', 'string'),
      'Returns a documentation string for the specified method')
  );
  // the order matters in the next line. which is the best?
  foreach (array_merge($defaults, (array)$callbacks) as $key => $callback) {
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
     die('XML-RPC server accepts POST requests only.');
  }
  $xmlrpc_server->message = xmlrpc_message($data);
  if (!xmlrpc_message_parse($xmlrpc_server->message)) {
    xmlrpc_server_error(-32700, t('parse error. not well formed'));
  }
  if ($xmlrpc_server->message->messagetype != 'methodCall') {
    xmlrpc_server_error(-32600, t('server error. invalid xml-rpc. not conforming to spec. Request must be a method_call'));
  }
  xmlrpc_server_set($xmlrpc_server);
  $result = xmlrpc_server_call($xmlrpc_server, $xmlrpc_server->message->methodname, $xmlrpc_server->message->params);
  // Is the result an error?
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
    <value>'.
    xmlrpc_value_get_xml($r)
    .'</value>
  </param>
  </params>
</methodResponse>

';
  // Send it
  xmlrpc_server_output($xml);
}

function xmlrpc_server_error($error, $message = false) {
  // Accepts either an error object or an error code and message
  if ($message && !is_object($error)) {
    $error = xmlrpc_error($error, $message);
  }
  xmlrpc_server_output(xmlrpc_error_get_xml($error));
}

function xmlrpc_server_output($xml) {
  $xml = '<?xml version="1.0"?>'."\n". $xml;
  header('Connection: close');
  header('Content-Length: '. strlen($xml));
  header('Content-Type: text/xml');
  header('Date: '.date('r'));
  echo $xml;
  exit;
}

function xmlrpc_server_list_methods() {
  $xmlrpc_server = xmlrpc_server_get();
  return array_keys($xmlrpc_server->callbacks);
}

function xmlrpc_server_set($xmlrpc_server = NULL) {
  static $server;
  if (!isset($server)) {
    $server = $xmlrpc_server;
  }
  return $server;
}

function xmlrpc_server_get() {
  return xmlrpc_server_set();
}

function xmlrpc_server_multicall($methodcalls) {
  // See http://www.xmlrpc.com/discuss/msgReader$1208
  $return = array();
  $xmlrpc_server = xmlrpc_server_get();
  foreach ($methodcalls as $call) {
    $method = $call['methodName'];
    $params = $call['params'];
    if ($method == 'system.multicall') {
      $result = xmlrpc_error(-32600, t('Recursive calls to system.multicall are forbidden'));
    } else {
      $result = xmlrpc_server_call($xmlrpc_server, $method, $params);
    }
    if ($result->is_error) {
      $return[] = array(
        'faultCode' => $result->code,
        'faultString' => $result->message
      );
    } else {
      $return[] = $result;
    }
  }
  return $return;
}


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
    'specUrl' => 'http://xmlrpc.usefulinc.com/doc/reserved.html',
    'specVersion' => 1
    )
  );
}


function xmlrpc_server_call($xmlrpc_server, $methodname, $args) {
  // Make sure it's in an array
  if ($args && !is_array($args)) {
    $args = array($args);
  }
  // Over-rides default call method, adds signature check
  if (!isset($xmlrpc_server->callbacks[$methodname])) {
    return xmlrpc_error(-32601, t('server error. requested method %methodname not specified.',array("%methodname" => $xmlrpc_server->message->methodname)));
  }
  $method = $xmlrpc_server->callbacks[$methodname];
  $signature = $xmlrpc_server->signatures[$methodname];

  $ok = true;
  if (is_array($signature)) {
    $return_type = array_shift($signature);
    // Check the number of arguments
    if (count($args) != count($signature)) {
      return xmlrpc_error(-32602, t('server error. wrong number of method parameters'));
    }
    // Check the argument types
    foreach ($signature as $key => $type) {
      $arg = $args[$key];
      switch ($type) {
        case 'int':
        case 'i4':
          if (is_array($arg) || !is_int($arg)) {
            $ok = false;
          }
          break;
        case 'base64':
        case 'string':
          if (!is_string($arg)) {
            $ok = false;
          }
          break;
        case 'boolean':
          if ($arg !== false && $arg !== true) {
            $ok = false;
          }
          break;
        case 'float':
        case 'double':
          if (!is_float($arg)) {
            $ok = false;
          }
          break;
        case 'date':
        case 'dateTime.iso8601':
          if (!$arg->is_date) {
            $ok = false;
          }
          break;
      }
      if (!$ok) {
        return xmlrpc_error(-32602, t('server error. invalid method parameters'));
      }
    }
  }
  // It passed the test - run the "real" method call
  if (!isset($xmlrpc_server->callbacks[$methodname])) {
    return xmlrpc_error(-32601, t('server error. requested method %methodname does not exist.',array("%methodname" => $methodname)));
  }

  $method = $xmlrpc_server->callbacks[$methodname];
  // Perform the callback and send the response
/*
  if (count($args) == 1) {
    // If only one paramater just send that instead of the whole array
    $args = $args[0];
  }
*/
  if (!function_exists($method)) {
    return xmlrpc_error(-32601, t('server error. requested function %method does not exist.', array("%method" => $method)));
  }
  // Call the function
  return call_user_func_array($method, $args);
}

function xmlrpc_server_method_signature($methodname) {
  $xmlrpc_server = xmlrpc_server_get();
  if (!isset($xmlrpc_server->callbacks[$methodname])) {
    return xmlrpc_error(-32601, t('server error. requested method %methodname not specified.', array("%methodname" => $methodname)));
  }
  if (!is_array($xmlrpc_server->signatures[$methodname])) {
    return xmlrpc_error(-32601, t('server error. requested method %methodname signature not specified.', array("%methodname" => $methodname)));
  }
  // We should be returning an array of types
  $return = array();
  foreach ($xmlrpc_server->signatures[$methodname] as $type) {
    switch ($type) {
      case 'string':
        $return[] = 'string';
        break;
      case 'int':
      case 'i4':
        $return[] = 42;
        break;
      case 'double':
        $return[] = 3.1415;
        break;
      case 'dateTime.iso8601':
        $return[] = xmlrpc_date(time());
        break;
      case 'boolean':
        $return[] = true;
        break;
      case 'base64':
        $return[] =xmlrpc_base64('base64');
        break;
      case 'array':
        $return[] = array('array');
        break;
      case 'struct':
        $return[] = array('struct' => 'struct');
        break;
    }
  }
  return $return;
}

function xmlrpc_server_method_help($method) {
  $xmlrpc_server = xmlrpc_server_get();
  return $xmlrpc_server->help[$method];
}

?>
