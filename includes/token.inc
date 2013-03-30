<?php

/**
 * @file
 * Drupal placeholder/token replacement system.
 *
 * API functions for replacing placeholders in text with meaningful values.
 *
 * For example: When configuring automated emails, an administrator enters
 * standard text for the email. Variables like the title of a node and the date
 * the email was sent can be entered as placeholders like [node:title] and
 * [date:short]. When a Drupal module prepares to send the email, it can call
 * the token_replace() function, passing in the text. The token system will
 * scan the text for placeholder tokens, give other modules an opportunity to
 * replace them with meaningful text, then return the final product to the
 * original module.
 *
 * Tokens follow the form: [$type:$name], where $type is a general class of
 * tokens like 'node', 'user', or 'comment' and $name is the name of a given
 * placeholder. For example, [node:title] or [node:created:since].
 *
 * In addition to raw text containing placeholders, modules may pass in an array
 * of objects to be used when performing the replacement. The objects should be
 * keyed by the token type they correspond to. For example:
 *
 * @code
 * // Load a node and a user, then replace tokens in the text.
 * $text = 'On [date:short], [user:name] read [node:title].';
 * $node = node_load(1);
 * $user = user_load(1);
 *
 * // [date:...] tokens use the current date automatically.
 * $data = array('node' => $node, 'user' => $user);
 * return token_replace($text, $data);
 * @endcode
 *
 * Some tokens may be chained in the form of [$type:$pointer:$name], where $type
 * is a normal token type, $pointer is a reference to another token type, and
 * $name is the name of a given placeholder. For example, [node:author:mail]. In
 * that example, 'author' is a pointer to the 'user' account that created the
 * node, and 'mail' is a placeholder available for any 'user'.
 *
 * @see token_replace()
 * @see hook_tokens()
 * @see hook_token_info()
 */

/**
 * Replaces all tokens in a given string with appropriate values.
 *
 * @param $text
 *   A string potentially containing replaceable tokens.
 * @param $data
 *   (optional) An array of keyed objects. For simple replacement scenarios
 *   'node', 'user', and others are common keys, with an accompanying node or
 *   user object being the value. Some token types, like 'site', do not require
 *   any explicit information from $data and can be replaced even if it is
 *   empty.
 * @param $options
 *   (optional) A keyed array of settings and flags to control the token
 *   replacement process. Supported options are:
 *   - language: A language object to be used when generating locale-sensitive
 *     tokens.
 *   - callback: A callback function that will be used to post-process the array
 *     of token replacements after they are generated. For example, a module
 *     using tokens in a text-only email might provide a callback to strip HTML
 *     entities from token values before they are inserted into the final text.
 *   - clear: A boolean flag indicating that tokens should be removed from the
 *     final text if no replacement value can be generated.
 *   - sanitize: A boolean flag indicating that tokens should be sanitized for
 *     display to a web browser. Defaults to TRUE. Developers who set this
 *     option to FALSE assume responsibility for running filter_xss(),
 *     check_plain() or other appropriate scrubbing functions before displaying
 *     data to users.
 *
 * @return
 *   Text with tokens replaced.
 */
function token_replace($text, array $data = array(), array $options = array()) {
  $text_tokens = token_scan($text);
  if (empty($text_tokens)) {
    return $text;
  }

  $replacements = array();
  foreach ($text_tokens as $type => $tokens) {
    $replacements += token_generate($type, $tokens, $data, $options);
    if (!empty($options['clear'])) {
      $replacements += array_fill_keys($tokens, '');
    }
  }

  // Optionally alter the list of replacement values.
  if (!empty($options['callback']) && function_exists($options['callback'])) {
    $function = $options['callback'];
    $function($replacements, $data, $options);
  }

  $tokens = array_keys($replacements);
  $values = array_values($replacements);

  return str_replace($tokens, $values, $text);
}

/**
 * Builds a list of all token-like patterns that appear in the text.
 *
 * @param $text
 *   The text to be scanned for possible tokens.
 *
 * @return
 *   An associative array of discovered tokens, grouped by type.
 */
function token_scan($text) {
  // Matches tokens with the following pattern: [$type:$name]
  // $type and $name may not contain  [ ] characters.
  // $type may not contain : or whitespace characters, but $name may.
  preg_match_all('/
    \[             # [ - pattern start
    ([^\s\[\]:]*)  # match $type not containing whitespace : [ or ]
    :              # : - separator
    ([^\[\]]*)     # match $name not containing [ or ]
    \]             # ] - pattern end
    /x', $text, $matches);

  $types = $matches[1];
  $tokens = $matches[2];

  // Iterate through the matches, building an associative array containing
  // $tokens grouped by $types, pointing to the version of the token found in
  // the source text. For example, $results['node']['title'] = '[node:title]';
  $results = array();
  for ($i = 0; $i < count($tokens); $i++) {
    $results[$types[$i]][$tokens[$i]] = $matches[0][$i];
  }

  return $results;
}

/**
 * Generates replacement values for a list of tokens.
 *
 * @param $type
 *   The type of token being replaced. 'node', 'user', and 'date' are common.
 * @param $tokens
 *   An array of tokens to be replaced, keyed by the literal text of the token
 *   as it appeared in the source text.
 * @param $data
 *   (optional) An array of keyed objects. For simple replacement scenarios
 *   'node', 'user', and others are common keys, with an accompanying node or
 *   user object being the value. Some token types, like 'site', do not require
 *   any explicit information from $data and can be replaced even if it is
 *   empty.
 * @param $options
 *   (optional) A keyed array of settings and flags to control the token
 *   replacement process. Supported options are:
 *   - language: A language object to be used when generating locale-sensitive
 *     tokens.
 *   - callback: A callback function that will be used to post-process the
 *     array of token replacements after they are generated. Can be used when
 *     modules require special formatting of token text, for example URL
 *     encoding or truncation to a specific length.
 *   - sanitize: A boolean flag indicating that tokens should be sanitized for
 *     display to a web browser. Developers who set this option to FALSE assume
 *     responsibility for running filter_xss(), check_plain() or other
 *     appropriate scrubbing functions before displaying data to users.
 *
 * @return
 *   An associative array of replacement values, keyed by the original 'raw'
 *   tokens that were found in the source text. For example:
 *   $results['[node:title]'] = 'My new node';
 *
 * @see hook_tokens()
 * @see hook_tokens_alter()
 */
function token_generate($type, array $tokens, array $data = array(), array $options = array()) {
  $options += array('sanitize' => TRUE);
  $replacements = module_invoke_all('tokens', $type, $tokens, $data, $options);

  // Allow other modules to alter the replacements.
  $context = array(
    'type' => $type,
    'tokens' => $tokens,
    'data' => $data,
    'options' => $options,
  );
  drupal_alter('tokens', $replacements, $context);

  return $replacements;
}

/**
 * Returns a list of tokens that begin with a specific prefix.
 *
 * Used to extract a group of 'chained' tokens (such as [node:author:name])
 * from the full list of tokens found in text. For example:
 * @code
 *   $data = array(
 *     'author:name' => '[node:author:name]',
 *     'title'       => '[node:title]',
 *     'created'     => '[node:created]',
 *   );
 *   $results = token_find_with_prefix($data, 'author');
 *   $results == array('name' => '[node:author:name]');
 * @endcode
 *
 * @param $tokens
 *   A keyed array of tokens, and their original raw form in the source text.
 * @param $prefix
 *   A textual string to be matched at the beginning of the token.
 * @param $delimiter
 *   An optional string containing the character that separates the prefix from
 *   the rest of the token. Defaults to ':'.
 *
 * @return
 *   An associative array of discovered tokens, with the prefix and delimiter
 *   stripped from the key.
 */
function token_find_with_prefix(array $tokens, $prefix, $delimiter = ':') {
  $results = array();
  foreach ($tokens as $token => $raw) {
    $parts = explode($delimiter, $token, 2);
    if (count($parts) == 2 && $parts[0] == $prefix) {
      $results[$parts[1]] = $raw;
    }
  }
  return $results;
}

/**
 * Returns metadata describing supported tokens.
 *
 * The metadata array contains token type, name, and description data as well
 * as an optional pointer indicating that the token chains to another set of
 * tokens.
 *
 * For example:
 * @code
 *   $data['types']['node'] = array(
 *     'name' => t('Nodes'),
 *     'description' => t('Tokens related to node objects.'),
 *   );
 *   $data['tokens']['node']['title'] = array(
 *     'name' => t('Title'),
 *     'description' => t('The title of the current node.'),
 *   );
 *   $data['tokens']['node']['author'] = array(
 *     'name' => t('Author'),
 *     'description' => t('The author of the current node.'),
 *     'type' => 'user',
 *   );
 * @endcode
 *
 * @return
 *   An associative array of token information, grouped by token type.
 */
function token_info() {
  $data = &drupal_static(__FUNCTION__);
  if (!isset($data)) {
    $data = module_invoke_all('token_info');
    drupal_alter('token_info', $data);
  }
  return $data;
}
