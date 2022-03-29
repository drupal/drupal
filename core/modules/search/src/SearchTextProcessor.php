<?php

namespace Drupal\search;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Processes search text for indexing.
 */
class SearchTextProcessor implements SearchTextProcessorInterface {

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * SearchTextProcessor constructor.
   *
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(TransliterationInterface $transliteration, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->transliteration = $transliteration;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function process(string $text, ?string $langcode = NULL): array {
    $text = $this->analyze($text, $langcode);
    return explode(' ', $text);
  }

  /**
   * {@inheritdoc}
   */
  public function analyze(string $text, ?string $langcode = NULL): string {
    // Decode entities to UTF-8.
    $text = Html::decodeEntities($text);

    // Lowercase.
    $text = mb_strtolower($text);

    // Remove diacritics.
    $text = $this->transliteration->removeDiacritics($text);

    // Call an external processor for word handling.
    $this->invokePreprocess($text, $langcode);

    // Simple CJK handling.
    if ($this->configFactory->get('search.settings')->get('index.overlap_cjk')) {
      $text = preg_replace_callback('/[' . self::PREG_CLASS_CJK . ']+/u', [$this, 'expandCjk'], $text);
    }

    // To improve searching for numerical data such as dates, IP addresses
    // or version numbers, we consider a group of numerical characters
    // separated only by punctuation characters to be one piece.
    // This also means that searching for e.g. '20/03/1984' also returns
    // results with '20-03-1984' in them.
    // Readable regexp: ([number]+)[punctuation]+(?=[number])
    $text = preg_replace('/([' . self::PREG_CLASS_NUMBERS . ']+)[' . self::PREG_CLASS_PUNCTUATION . ']+(?=[' . self::PREG_CLASS_NUMBERS . '])/u', '\1', $text);

    // Multiple dot and dash groups are word boundaries and replaced with space.
    // No need to use the unicode modifier here because 0-127 ASCII characters
    // can't match higher UTF-8 characters as the leftmost bit of those are 1.
    $text = preg_replace('/[.-]{2,}/', ' ', $text);

    // The dot, underscore and dash are simply removed. This allows meaningful
    // search behavior with acronyms and URLs. See unicode note directly above.
    $text = preg_replace('/[._-]+/', '', $text);

    // With the exception of the rules above, we consider all punctuation,
    // marks, spacers, etc, to be a word boundary.
    $text = preg_replace('/[' . Unicode::PREG_CLASS_WORD_BOUNDARY . ']+/u', ' ', $text);

    // Truncate everything to 50 characters.
    $words = explode(' ', $text);
    array_walk($words, [$this, 'truncate']);
    $text = implode(' ', $words);

    return $text;
  }

  /**
   * Invokes hook_search_preprocess() to simplify text.
   *
   * @param string $text
   *   Text to preprocess, passed by reference and altered in place.
   * @param string|null $langcode
   *   Language code for the language of $text, if known.
   */
  protected function invokePreprocess(string &$text, ?string $langcode = NULL): void {
    $this->moduleHandler->invokeAllWith(
      'search_preprocess',
      function (callable $hook, string $module) use (&$text, &$langcode) {
        $text = $hook($text, $langcode);
      }
    );
  }

  /**
   * Splits CJK (Chinese, Japanese, Korean) text into tokens.
   *
   * The Search module matches exact words, where a word is defined to be a
   * sequence of characters delimited by spaces or punctuation. CJK languages
   * are written in long strings of characters, though, not split up into words.
   * So in order to allow search matching, we split up CJK text into tokens
   * consisting of consecutive, overlapping sequences of characters whose length
   * is equal to the 'minimum_word_size' variable. This tokenizing is only done
   * if the 'overlap_cjk' variable is TRUE.
   *
   * @param array $matches
   *   This function is a callback for preg_replace_callback(), which is called
   *   from self::analyze(). So, $matches is an array of regular expression
   *   matches, which means that $matches[0] contains the matched text -- a
   *   string of CJK characters to tokenize.
   *
   * @return string
   *   Tokenized text, starting and ending with a space character.
   */
  protected function expandCjk(array $matches): string {
    $min = $this->configFactory->get('search.settings')->get('index.minimum_word_size');
    $str = $matches[0];
    $length = mb_strlen($str);
    // If the text is shorter than the minimum word size, don't tokenize it.
    if ($length <= $min) {
      return ' ' . $str . ' ';
    }
    $tokens = ' ';
    // Build a FIFO queue of characters.
    $chars = [];
    for ($i = 0; $i < $length; $i++) {
      // Add the next character off the beginning of the string to the queue.
      $current = mb_substr($str, 0, 1);
      $str = substr($str, strlen($current));
      $chars[] = $current;
      if ($i >= $min - 1) {
        // Make a token of $min characters, and add it to the token string.
        $tokens .= implode('', $chars) . ' ';
        // Shift out the first character in the queue.
        array_shift($chars);
      }
    }
    return $tokens;
  }

  /**
   * Helper function for array_walk in ::analyze().
   *
   * @param string $text
   *   The text to be truncated.
   */
  protected function truncate(string &$text): void {
    if (is_numeric($text)) {
      $text = ltrim($text, '0');
    }
    if (mb_strlen($text) <= 50) {
      return;
    }
    $text = mb_substr($text, 0, 50);
  }

}
