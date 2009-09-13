<?php
// $Id$

/**
 * @file
 * Hooks provided by the OpenID module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allow modules to modify the OpenID request parameters.
 *
 * @param $op
 *   The operation to be performed.
 *   Possible values:
 *   - request: Modify parameters before they are sent to the OpenID provider.
 * @param $request
 *   An associative array of parameter defaults to which to modify or append.
 * @return
 *   An associative array of parameters to be merged with the default list.
 *
 */
function hook_openid($op, $request) {
  if ($op == 'request') {
    $request['openid.identity'] = 'http://myname.myopenid.com/';
  }
  return $request;
}

/**
 * Allow modules to act upon a successful OpenID login.
 *
 * @param $response
 *   Response values from the OpenID Provider.
 * @param $account
 *   The Drupal user account that logged in
 *
 */
function hook_openid_response($response, $account) {
  if (isset($response['openid.ns.ax'])) {
    _mymodule_store_ax_fields($response, $account);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
