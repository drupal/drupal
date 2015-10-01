<?php

/**
 * @file
 * Contains \Drupal\Core\Utility\Token.
 */

namespace Drupal\Core\Utility;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;

/**
 * Drupal placeholder/token replacement system.
 *
 * API functions for replacing placeholders in text with meaningful values.
 *
 * For example: When configuring automated emails, an administrator enters
 * standard text for the email. Variables like the title of a node and the date
 * the email was sent can be entered as placeholders like [node:title] and
 * [date:short]. When a Drupal module prepares to send the email, it can call
 * the Token::replace() function, passing in the text. The token system will
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
 * $node = Node::load(1);
 * $user = User::load(1);
 *
 * // [date:...] tokens use the current date automatically.
 * $data = array('node' => $node, 'user' => $user);
 * return Token::replace($text, $data);
 * @endcode
 *
 * Some tokens may be chained in the form of [$type:$pointer:$name], where $type
 * is a normal token type, $pointer is a reference to another token type, and
 * $name is the name of a given placeholder. For example, [node:author:mail]. In
 * that example, 'author' is a pointer to the 'user' account that created the
 * node, and 'mail' is a placeholder available for any 'user'.
 *
 * @see Token::replace()
 * @see hook_tokens()
 * @see hook_token_info()
 */
class Token {

  /**
   * The tag to cache token info with.
   */
  const TOKEN_INFO_CACHE_TAG = 'token_info';

  /**
   * The token cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Token definitions.
   *
   * @var array[]|null
   *   An array of token definitions, or NULL when the definitions are not set.
   *
   * @see self::setInfo()
   * @see self::getInfo()
   * @see self::resetInfo()
   */
  protected $tokenInfo;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The token cache.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, CacheTagsInvalidatorInterface $cache_tags_invalidator, RendererInterface $renderer) {
    $this->cache = $cache;
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->renderer = $renderer;
  }

  /**
   * Replaces all tokens in a given string with appropriate values.
   *
   * @param string $text
   *   A string potentially containing replaceable tokens.
   * @param array $data
   *   (optional) An array of keyed objects. For simple replacement scenarios
   *   'node', 'user', and others are common keys, with an accompanying node or
   *   user object being the value. Some token types, like 'site', do not require
   *   any explicit information from $data and can be replaced even if it is
   *   empty.
   * @param array $options
   *   (optional) A keyed array of settings and flags to control the token
   *   replacement process. Supported options are:
   *   - langcode: A language code to be used when generating locale-sensitive
   *     tokens.
   *   - callback: A callback function that will be used to post-process the
   *     array of token replacements after they are generated. For example, a
   *     module using tokens in a text-only email might provide a callback to
   *     strip HTML entities from token values before they are inserted into the
   *     final text.
   *   - clear: A boolean flag indicating that tokens should be removed from the
   *     final text if no replacement value can be generated.
   *   - sanitize: A boolean flag indicating that tokens should be sanitized for
   *     display to a web browser. Defaults to TRUE. Developers who set this
   *     option to FALSE assume responsibility for running
   *     \Drupal\Component\Utility\Xss::filter(),
   *     \Drupal\Component\Utility\Html::escape() or other appropriate scrubbing
   *     functions before displaying data to users.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata|null
   *   (optional) An object to which static::generate() and the hooks and
   *   functions that it invokes will add their required bubbleable metadata.
   *
   *   To ensure that the metadata associated with the token replacements gets
   *   attached to the same render array that contains the token-replaced text,
   *   callers of this method are encouraged to pass in a BubbleableMetadata
   *   object and apply it to the corresponding render array. For example:
   *   @code
   *     $bubbleable_metadata = new BubbleableMetadata();
   *     $build['#markup'] = $token_service->replace('Tokens: [node:nid] [current-user:uid]', ['node' => $node], [], $bubbleable_metadata);
   *     $bubbleable_metadata->applyTo($build);
   *   @endcode
   *
   *   When the caller does not pass in a BubbleableMetadata object, this
   *   method creates a local one, and applies the collected metadata to the
   *   Renderer's currently active render context.
   *
   * @return string
   *   Text with tokens replaced.
   */
  public function replace($text, array $data = array(), array $options = array(), BubbleableMetadata $bubbleable_metadata = NULL) {
    $text_tokens = $this->scan($text);
    if (empty($text_tokens)) {
      return $text;
    }

    $bubbleable_metadata_is_passed_in = (bool) $bubbleable_metadata;
    $bubbleable_metadata = $bubbleable_metadata ?: new BubbleableMetadata();

    $replacements = array();
    foreach ($text_tokens as $type => $tokens) {
      $replacements += $this->generate($type, $tokens, $data, $options, $bubbleable_metadata);
      if (!empty($options['clear'])) {
        $replacements += array_fill_keys($tokens, '');
      }
    }

    // Optionally alter the list of replacement values.
    if (!empty($options['callback'])) {
      $function = $options['callback'];
      $function($replacements, $data, $options, $bubbleable_metadata);
    }

    $tokens = array_keys($replacements);
    $values = array_values($replacements);

    // If a local $bubbleable_metadata object was created, apply the metadata
    // it collected to the renderer's currently active render context.
    if (!$bubbleable_metadata_is_passed_in && $this->renderer->hasRenderContext()) {
      $build = [];
      $bubbleable_metadata->applyTo($build);
      $this->renderer->render($build);
    }

    return str_replace($tokens, $values, $text);
  }

  /**
   * Builds a list of all token-like patterns that appear in the text.
   *
   * @param string $text
   *   The text to be scanned for possible tokens.
   *
   * @return array
   *   An associative array of discovered tokens, grouped by type.
   */
  public function scan($text) {
    // Matches tokens with the following pattern: [$type:$name]
    // $type and $name may not contain [ ] characters.
    // $type may not contain : or whitespace characters, but $name may.
    preg_match_all('/
      \[             # [ - pattern start
      ([^\s\[\]:]+)  # match $type not containing whitespace : [ or ]
      :              # : - separator
      ([^\[\]]+)     # match $name not containing [ or ]
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
   * @param string $type
   *   The type of token being replaced. 'node', 'user', and 'date' are common.
   * @param array $tokens
   *   An array of tokens to be replaced, keyed by the literal text of the token
   *   as it appeared in the source text.
   * @param array $data
   *   An array of keyed objects. For simple replacement scenarios: 'node',
   *   'user', and others are common keys, with an accompanying node or user
   *   object being the value. Some token types, like 'site', do not require
   *   any explicit information from $data and can be replaced even if it is
   *   empty.
   * @param array $options
   *   A keyed array of settings and flags to control the token replacement
   *   process. Supported options are:
   *   - langcode: A language code to be used when generating locale-sensitive
   *     tokens.
   *   - callback: A callback function that will be used to post-process the
   *     array of token replacements after they are generated. Can be used when
   *     modules require special formatting of token text, for example URL
   *     encoding or truncation to a specific length.
   *   - sanitize: A boolean flag indicating that tokens should be sanitized for
   *     display to a web browser. Developers who set this option to FALSE assume
   *     responsibility for running \Drupal\Component\Utility\Xss::filter(),
   *     \Drupal\Component\Utility\Html::escape() or other appropriate scrubbing
   *     functions before displaying data to users.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *    The bubbleable metadata. This is passed to the token replacement
   *    implementations so that they can attach their metadata.
   *
   * @return array
   *   An associative array of replacement values, keyed by the original 'raw'
   *   tokens that were found in the source text. For example:
   *   $results['[node:title]'] = 'My new node';
   *
   * @see hook_tokens()
   * @see hook_tokens_alter()
   */
  public function generate($type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $options += array('sanitize' => TRUE);

    foreach ($data as $object) {
      if ($object instanceof CacheableDependencyInterface || $object instanceof AttachmentsInterface) {
        $bubbleable_metadata->addCacheableDependency($object);
      }
    }

    $replacements = $this->moduleHandler->invokeAll('tokens', [$type, $tokens, $data, $options, $bubbleable_metadata]);

    // Allow other modules to alter the replacements.
    $context = array(
      'type' => $type,
      'tokens' => $tokens,
      'data' => $data,
      'options' => $options,
    );
    $this->moduleHandler->alter('tokens', $replacements, $context, $bubbleable_metadata);

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
   *   $results = Token::findWithPrefix($data, 'author');
   *   $results == array('name' => '[node:author:name]');
   * @endcode
   *
   * @param array $tokens
   *   A keyed array of tokens, and their original raw form in the source text.
   * @param string $prefix
   *   A textual string to be matched at the beginning of the token.
   * @param string $delimiter
   *   (optional) A string containing the character that separates the prefix from
   *   the rest of the token. Defaults to ':'.
   *
   * @return array
   *   An associative array of discovered tokens, with the prefix and delimiter
   *   stripped from the key.
   */
  public function findWithPrefix(array $tokens, $prefix, $delimiter = ':') {
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
   * @return array
   *   An associative array of token information, grouped by token type. The
   *   array structure is identical to that of hook_token_info().
   *
   * @see hook_token_info()
   */
  public function getInfo() {
    if (is_null($this->tokenInfo)) {
      $cache_id = 'token_info:' . $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
      $cache = $this->cache->get($cache_id);
      if ($cache) {
        $this->tokenInfo = $cache->data;
      }
      else {
        $this->tokenInfo = $this->moduleHandler->invokeAll('token_info');
        $this->moduleHandler->alter('token_info', $this->tokenInfo);
        $this->cache->set($cache_id, $this->tokenInfo, CacheBackendInterface::CACHE_PERMANENT, array(
          static::TOKEN_INFO_CACHE_TAG,
        ));
      }
    }

    return $this->tokenInfo;
  }

  /**
   * Sets metadata describing supported tokens.
   *
   * @param array $tokens
   *   Token metadata that has an identical structure to the return value of
   *   hook_token_info().
   *
   * @see hook_token_info()
   */
  public function setInfo(array $tokens) {
    $this->tokenInfo = $tokens;
  }

  /**
   * Resets metadata describing supported tokens.
   */
  public function resetInfo() {
    $this->tokenInfo = NULL;
    $this->cacheTagsInvalidator->invalidateTags([static::TOKEN_INFO_CACHE_TAG]);
  }
}
