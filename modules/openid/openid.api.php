<?php

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
 * Allow modules to declare OpenID discovery methods.
 *
 * The discovery function callbacks will be called in turn with an unique
 * parameter, the claimed identifier. They have to return an array of services,
 * in the same form returned by openid_discover().
 *
 * The first discovery method that succeed (return at least one services) will
 * stop the discovery process.
 *
 * @return
 *   An associative array which keys are the name of the discovery methods and
 *   values are function callbacks.
 * @see hook_openid_discovery_method_info_alter()
 */
function hook_openid_discovery_method_info() {
  return array(
    'new_discovery_idea' => '_my_discovery_method',
  );
}

/**
 * Allow modules to alter discovery methods.
 */
function hook_openid_discovery_method_info_alter(&$methods) {
  // Remove XRI discovery scheme.
  unset($methods['xri']);
}

/**
 * Allow modules to declare OpenID normalization methods.
 *
 * The discovery function callbacks will be called in turn with an unique
 * parameter, the identifier to normalize. They have to return a normalized
 * identifier, or NULL if the identifier is not in a form they can handle.
 *
 * The first normalization method that succeed (return a value that is not NULL)
 * will stop the normalization process.
 *
 * @return
 *   An array with a set of function callbacks, that will be called in turn
 *   when normalizing an OpenID identifier. The normalization functions have
 *   to return a normalized identifier, or NULL if the identifier is not in
 *   a form they can handle.
 * @see hook_openid_normalization_method_info_alter()
 */
function hook_openid_normalization_method_info() {
  return array(
    'new_normalization_idea' => '_my_normalization_method',
  );
}

/**
 * Allow modules to alter normalization methods.
 */
function hook_openid_normalization_method_info_alter(&$methods) {
  // Remove Google IDP normalization.
  unset($methods['google_idp']);
}

/**
 * @} End of "addtogroup hooks".
 */
