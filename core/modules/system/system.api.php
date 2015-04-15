<?php

/**
 * @file
 * Hooks provided by the System module.
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters theme operation links.
 *
 * @param $theme_groups
 *   An associative array containing groups of themes.
 *
 * @see system_themes_page()
 */
function hook_system_themes_page_alter(&$theme_groups) {
  foreach ($theme_groups as $state => &$group) {
    foreach ($theme_groups[$state] as &$theme) {
      // Add a foo link to each list of theme operations.
      $theme->operations[] = array(
        'title' => t('Foo'),
        'url' => Url::fromRoute('system.themes_page'),
        'query' => array('theme' => $theme->getName())
      );
    }
  }
}

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
 * @param $data
 *   (optional) An associative array of data objects to be used when generating
 *   replacement values, as supplied in the $data parameter to
 *   \Drupal\Core\Utility\Token::replace().
 * @param $options
 *   (optional) An associative array of options for token replacement; see
 *   \Drupal\Core\Utility\Token::replace() for possible values.
 *
 * @return
 *   An associative array of replacement values, keyed by the raw [type:token]
 *   strings from the original text.
 *
 * @see hook_token_info()
 * @see hook_tokens_alter()
 */
function hook_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $token_service = \Drupal::token();

  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $sanitize = !empty($options['sanitize']);

  $replacements = array();

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
          $replacements[$original] = $sanitize ? SafeMarkup::checkPlain($node->getTitle()) : $node->getTitle();
          break;

        case 'edit-url':
          $replacements[$original] = $node->url('edit-form', $url_options);
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          $account = $node->getOwner() ? $node->getOwner() : user_load(0);
          $replacements[$original] = $sanitize ? SafeMarkup::checkPlain($account->label()) : $account->label();
          break;

        case 'created':
          $replacements[$original] = format_date($node->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, array('user' => $node->getOwner()), $options);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, array('date' => $node->getCreatedTime()), $options);
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
 *
 * @see hook_tokens()
 */
function hook_tokens_alter(array &$replacements, array $context) {
  $options = $context['options'];

  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }

  if ($context['type'] == 'node' && !empty($context['data']['node'])) {
    $node = $context['data']['node'];

    // Alter the [node:title] token, and replace it with the rendered content
    // of a field (field_title).
    if (isset($context['tokens']['title'])) {
      $title = $node->field_title->view('default');
      $replacements[$context['tokens']['title']] = drupal_render($title);
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
 * @return
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
  $type = array(
    'name' => t('Nodes'),
    'description' => t('Tokens related to individual nodes.'),
    'needs-data' => 'node',
  );

  // Core tokens for nodes.
  $node['nid'] = array(
    'name' => t("Node ID"),
    'description' => t("The unique ID of the node."),
  );
  $node['title'] = array(
    'name' => t("Title"),
  );
  $node['edit-url'] = array(
    'name' => t("Edit URL"),
    'description' => t("The URL of the node's edit page."),
  );

  // Chained tokens for nodes.
  $node['created'] = array(
    'name' => t("Date created"),
    'type' => 'date',
  );
  $node['author'] = array(
    'name' => t("Author"),
    'type' => 'user',
  );

  return array(
    'types' => array('node' => $type),
    'tokens' => array('node' => $node),
  );
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
  $data['tokens']['node']['nid'] = array(
    'name' => t("Node ID"),
    'description' => t("The unique ID of the article."),
  );
  $data['tokens']['node']['title'] = array(
    'name' => t("Title"),
    'description' => t("The title of the article."),
  );

  // Chained tokens for nodes.
  $data['tokens']['node']['created'] = array(
    'name' => t("Date created"),
    'description' => t("The date the article was posted."),
    'type' => 'date',
  );
}

/**
 * @} End of "addtogroup hooks".
 */
