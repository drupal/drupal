<?php

/**
 * @file
 * Hooks related to the Token system.
 */

use Drupal\user\Entity\User;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide replacement values for placeholder tokens.
 *
 * This hook is invoked when someone calls
 * \Drupal\Core\Utility\Token::replace(). That function first scans the text for
 * [type:token] patterns, and splits the needed tokens into groups by type.
 * Then hook_tokens() is invoked on each token-type group, allowing your module
 * to respond by providing replacement text for any of the tokens in the group
 * that your module knows how to process.
 *
 * A module implementing this hook should also implement hook_token_info() in
 * order to list its available tokens on editing screens.
 *
 * @param $type
 *   The machine-readable name of the type (group) of token being replaced, such
 *   as 'node', 'user', or another type defined by a hook_token_info()
 *   implementation.
 * @param $tokens
 *   An array of tokens to be replaced. The keys are the machine-readable token
 *   names, and the values are the raw [type:token] strings that appeared in the
 *   original text.
 * @param array $data
 *   An associative array of data objects to be used when generating replacement
 *   values, as supplied in the $data parameter to
 *   \Drupal\Core\Utility\Token::replace().
 * @param array $options
 *   An associative array of options for token replacement; see
 *   \Drupal\Core\Utility\Token::replace() for possible values.
 * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
 *   The bubbleable metadata. Prior to invoking this hook,
 *   \Drupal\Core\Utility\Token::generate() collects metadata for all of the
 *   data objects in $data. For any data sources not in $data, but that are
 *   used by the token replacement logic, such as global configuration (e.g.,
 *   'system.site') and related objects (e.g., $node->getOwner()),
 *   implementations of this hook must add the corresponding metadata.
 *   For example:
 *   @code
 *     $bubbleable_metadata->addCacheableDependency(\Drupal::config('system.site'));
 *     $bubbleable_metadata->addCacheableDependency($node->getOwner());
 *   @endcode
 *
 *   Additionally, implementations of this hook, must forward
 *   $bubbleable_metadata to the chained tokens that they invoke.
 *   For example:
 *   @code
 *     if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
 *       $replacements = $token_service->generate('date', $created_tokens, array('date' => $node->getCreatedTime()), $options, $bubbleable_metadata);
 *     }
 *   @endcode
 *
 * @return array
 *   An associative array of replacement values, keyed by the raw [type:token]
 *   strings from the original text. The returned values must be either plain
 *   text strings, or an object implementing MarkupInterface if they are
 *   HTML-formatted.
 *
 * @see hook_token_info()
 * @see hook_tokens_alter()
 */
function hook_tokens($type, $tokens, array $data, array $options, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $replacements = [];

  if ($type == 'node' && !empty($data['node'])) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the node.
        case 'nid':
          $replacements[$original] = $node->nid;
          break;

        case 'title':
          $replacements[$original] = $node->getTitle();
          break;

        case 'edit-url':
          $replacements[$original] = $node->toUrl('edit-form', $url_options)->toString();
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          $account = $node->getOwner() ? $node->getOwner() : User::load(0);
          $replacements[$original] = $account->label();
          $bubbleable_metadata->addCacheableDependency($account);
          break;

        case 'created':
          $replacements[$original] = \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, ['user' => $node->getOwner()], $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $node->getCreatedTime()], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}

/**
 * Alter replacement values for placeholder tokens.
 *
 * @param $replacements
 *   An associative array of replacements returned by hook_tokens().
 * @param $context
 *   The context in which hook_tokens() was called. An associative array with
 *   the following keys, which have the same meaning as the corresponding
 *   parameters of hook_tokens():
 *   - 'type'
 *   - 'tokens'
 *   - 'data'
 *   - 'options'
 * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
 *   The bubbleable metadata. In case you alter an existing token based upon
 *   a data source that isn't in $context['data'], you must add that
 *   dependency to $bubbleable_metadata.
 *
 * @see hook_tokens()
 */
function hook_tokens_alter(array &$replacements, array $context, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  if ($context['type'] == 'node' && !empty($context['data']['node'])) {
    $node = $context['data']['node'];

    // Alter the [node:title] token, and replace it with the rendered content
    // of a field (field_title).
    if (isset($context['tokens']['title'])) {
      $title = $node->field_title->view('default');
      $replacements[$context['tokens']['title']] = \Drupal::service('renderer')->render($title);
    }
  }
}

/**
 * Provide information about available placeholder tokens and token types.
 *
 * Tokens are placeholders that can be put into text by using the syntax
 * [type:token], where type is the machine-readable name of a token type, and
 * token is the machine-readable name of a token within this group. This hook
 * provides a list of types and tokens to be displayed on text editing screens,
 * so that people editing text can see what their token options are.
 *
 * The actual token replacement is done by
 * \Drupal\Core\Utility\Token::replace(), which invokes hook_tokens(). Your
 * module will need to implement that hook in order to generate token
 * replacements from the tokens defined here.
 *
 * @return array
 *   An associative array of available tokens and token types. The outer array
 *   has two components:
 *   - types: An associative array of token types (groups). Each token type is
 *     an associative array with the following components:
 *     - name: The translated human-readable short name of the token type.
 *     - description (optional): A translated longer description of the token
 *       type.
 *     - needs-data: The type of data that must be provided to
 *       \Drupal\Core\Utility\Token::replace() in the $data argument (i.e., the
 *       key name in $data) in order for tokens of this type to be used in the
 *       $text being processed. For instance, if the token needs a node object,
 *       'needs-data' should be 'node', and to use this token in
 *       \Drupal\Core\Utility\Token::replace(), the caller needs to supply a
 *       node object as $data['node']. Some token data can also be supplied
 *       indirectly; for instance, a node object in $data supplies a user object
 *       (the author of the node), allowing user tokens to be used when only
 *       a node data object is supplied.
 *   - tokens: An associative array of tokens. The outer array is keyed by the
 *     group name (the same key as in the types array). Within each group of
 *     tokens, each token item is keyed by the machine name of the token, and
 *     each token item has the following components:
 *     - name: The translated human-readable short name of the token.
 *     - description (optional): A translated longer description of the token.
 *     - type (optional): A 'needs-data' data type supplied by this token, which
 *       should match a 'needs-data' value from another token type. For example,
 *       the node author token provides a user object, which can then be used
 *       for token replacement data in \Drupal\Core\Utility\Token::replace()
 *       without having to supply a separate user object.
 *
 * @see hook_token_info_alter()
 * @see hook_tokens()
 */
function hook_token_info() {
  $type = [
    'name' => t('Nodes'),
    'description' => t('Tokens related to individual nodes.'),
    'needs-data' => 'node',
  ];

  // Core tokens for nodes.
  $node['nid'] = [
    'name' => t("Node ID"),
    'description' => t("The unique ID of the node."),
  ];
  $node['title'] = [
    'name' => t("Title"),
  ];
  $node['edit-url'] = [
    'name' => t("Edit URL"),
    'description' => t("The URL of the node's edit page."),
  ];

  // Chained tokens for nodes.
  $node['created'] = [
    'name' => t("Date created"),
    'type' => 'date',
  ];
  $node['author'] = [
    'name' => t("Author"),
    'type' => 'user',
  ];

  return [
    'types' => ['node' => $type],
    'tokens' => ['node' => $node],
  ];
}

/**
 * Alter the metadata about available placeholder tokens and token types.
 *
 * @param $data
 *   The associative array of token definitions from hook_token_info().
 *
 * @see hook_token_info()
 */
function hook_token_info_alter(&$data) {
  // Modify description of node tokens for our site.
  $data['tokens']['node']['nid'] = [
    'name' => t("Node ID"),
    'description' => t("The unique ID of the article."),
  ];
  $data['tokens']['node']['title'] = [
    'name' => t("Title"),
    'description' => t("The title of the article."),
  ];

  // Chained tokens for nodes.
  $data['tokens']['node']['created'] = [
    'name' => t("Date created"),
    'description' => t("The date the article was posted."),
    'type' => 'date',
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
