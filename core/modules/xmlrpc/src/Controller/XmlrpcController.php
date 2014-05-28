<?php

/**
 * @file
 * Contains \Drupal\xmlrpc\Controller\XmlrpcController.
 */

namespace Drupal\xmlrpc\Controller;

/**
 * Contains controller methods for the XML-RPC module.
 */
class XmlrpcController {

  /**
   * @todo Remove xmlrpc_server_page().
   */
  public function php() {
    module_load_include('server.inc', 'xmlrpc');
    return xmlrpc_server_page();
  }

}
