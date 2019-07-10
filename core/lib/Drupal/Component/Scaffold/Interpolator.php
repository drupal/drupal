<?php

namespace Drupal\Component\Scaffold;

/**
 * Injects config values from an associative array into a string.
 */
class Interpolator {

  /**
   * The character sequence that identifies the start of a token.
   *
   * @var string
   */
  protected $startToken;

  /**
   * The character sequence that identifies the end of a token.
   *
   * @var string
   */
  protected $endToken;

  /**
   * The associative array of replacements.
   *
   * @var array
   */
  protected $data = [];

  /**
   * Interpolator constructor.
   *
   * @param string $start_token
   *   The start marker for a token, e.g. '['.
   * @param string $end_token
   *   The end marker for a token, e.g. ']'.
   */
  public function __construct($start_token = '\\[', $end_token = '\\]') {
    $this->startToken = $start_token;
    $this->endToken = $end_token;
  }

  /**
   * Sets the data set to use when interpolating.
   *
   * @param array $data
   *   The key:value pairs to use when interpolating.
   *
   * @return $this
   */
  public function setData(array $data) {
    $this->data = $data;
    return $this;
  }

  /**
   * Adds to the data set to use when interpolating.
   *
   * @param array $data
   *   The key:value pairs to use when interpolating.
   *
   * @return $this
   */
  public function addData(array $data) {
    $this->data = array_merge($this->data, $data);
    return $this;
  }

  /**
   * Replaces tokens in a string with values from an associative array.
   *
   * Tokens are surrounded by delimiters, e.g. square brackets "[key]". The
   * characters that surround the key may be defined when the Interpolator is
   * constructed.
   *
   * Example:
   * If the message is 'Hello, [user.name]', then the value of the user.name
   * item is fetched from the array, and the token [user.name] is replaced with
   * the result.
   *
   * @param string $message
   *   Message containing tokens to be replaced.
   * @param array $extra
   *   Data to use for interpolation in addition to whatever was provided to
   *   self::setData().
   * @param string|bool $default
   *   (optional) The value to substitute for tokens that are not found in the
   *   data. If FALSE, then missing tokens are not replaced. Defaults to an
   *   empty string.
   *
   * @return string
   *   The message after replacements have been made.
   */
  public function interpolate($message, array $extra = [], $default = '') {
    $data = $extra + $this->data;
    $replacements = $this->replacements($message, $data, $default);
    return strtr($message, $replacements);
  }

  /**
   * Finds the tokens that exist in a message and builds a replacement array.
   *
   * All of the replacements in the data array are looked up given the token
   * keys from the provided message. Keys that do not exist in the configuration
   * are replaced with the default value.
   *
   * @param string $message
   *   String with tokens.
   * @param array $data
   *   Data to use for interpolation.
   * @param string $default
   *   (optional) The value to substitute for tokens that are not found in the
   *   data. If FALSE, then missing tokens are not replaced. Defaults to an
   *   empty string.
   *
   * @return string[]
   *   An array of replacements to make. Keyed by tokens and the replacements
   *   are the values.
   */
  protected function replacements($message, array $data, $default = '') {
    $tokens = $this->findTokens($message);
    $replacements = [];
    foreach ($tokens as $sourceText => $key) {
      $replacement_text = array_key_exists($key, $data) ? $data[$key] : $default;
      if ($replacement_text !== FALSE) {
        $replacements[$sourceText] = $replacement_text;
      }
    }
    return $replacements;
  }

  /**
   * Finds all of the tokens in the provided message.
   *
   * @param string $message
   *   String with tokens.
   *
   * @return string[]
   *   map of token to key, e.g. {{key}} => key
   */
  protected function findTokens($message) {
    $reg_ex = '#' . $this->startToken . '([a-zA-Z0-9._-]+)' . $this->endToken . '#';
    if (!preg_match_all($reg_ex, $message, $matches, PREG_SET_ORDER)) {
      return [];
    }
    $tokens = [];
    foreach ($matches as $matchSet) {
      list($sourceText, $key) = $matchSet;
      $tokens[$sourceText] = $key;
    }
    return $tokens;
  }

}
