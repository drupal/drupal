<?php

namespace Drupal\filter\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Provides a filter to convert URLs into links.
 */
#[Filter(
  id: "filter_url",
  title: new TranslatableMarkup("Convert URLs into links"),
  type: FilterInterface::TYPE_MARKUP_LANGUAGE,
  settings: [
    "filter_url_length" => 72,
  ]
)]
class FilterUrl extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The list of protocols that are allowed in URLs.
   */
  protected array $filterProtocols;

  /**
   * Temporary storage for HTML comments.
   */
  protected array $htmlComments;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    #[Autowire(param: 'filter_protocols')]
    ?array $filter_protocols = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if ($filter_protocols === NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $filter_protocols argument is deprecated in drupal:11.4.0 and will be required in drupal:12.0.0. See https://www.drupal.org/node/3566774', E_USER_DEPRECATED);
      $filter_protocols = \Drupal::getContainer()->getParameter('filter_protocols');
    }
    $this->filterProtocols = $filter_protocols;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['filter_url_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum link text length'),
      '#default_value' => $this->settings['filter_url_length'],
      '#min' => 1,
      '#field_suffix' => $this->t('characters'),
      '#description' => $this->t('URLs longer than this number of characters will be truncated to prevent long strings that break formatting. The link itself will be retained; just the text portion of the link will be truncated.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Store the current text in case any of the preg_* functions fail.
    $saved_text = $text;

    // Tags to skip and not recurse into.
    $ignore_tags = 'a|script|style|code|pre';

    // Create an array which contains the regexps for each type of link. The key
    // to the regexp is the name of a function that is used as a callback
    // function to process matches of the regexp. The callback function is to
    // return the replacement for the match. The array is used and
    // matching/replacement done below inside some loops.
    $tasks = [];

    // Prepare protocols pattern for absolute URLs.
    // \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols() will
    // replace any bad protocols with HTTP, so we need to support the identical
    // list. While '//' is technically optional for MAILTO only, we cannot
    // cleanly differ between protocols here without hard-coding MAILTO, so '//'
    // is optional for all protocols.
    // @see \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols()
    $protocols = implode(':(?://)?|', $this->filterProtocols) . ':(?://)?';

    $valid_url_path_characters = "[\p{L}\p{M}\p{N}!\*\';:=\+,\.\$\/%#\[\]\-_~@&]";

    // Allow URL paths to contain balanced parens
    // 1. Used in Wikipedia URLs like /Primer_(film)
    // 2. Used in IIS sessions like /S(dfd346)/
    $valid_url_balanced_parens = '\(' . $valid_url_path_characters . '+\)';

    // Valid end-of-path characters (so /foo. does not gobble the period). Allow
    // =&# for empty URL parameters and other URL-join artifacts
    $valid_url_ending_characters = '[\p{L}\p{M}\p{N}:_+~#=/]|(?:' . $valid_url_balanced_parens . ')';

    $valid_url_query_chars = '[a-zA-Z0-9!?\*\'@\(\);:&=\+\$\/%#\[\]\-_\.,~|]';
    $valid_url_query_ending_chars = '[a-zA-Z0-9_&=#\/]';

    // Full path and allow @ in a URL, but only in the middle. Catch things like
    // http://example.com/@user/
    $valid_url_path = '(?:(?:' . $valid_url_path_characters . '*(?:' . $valid_url_balanced_parens . $valid_url_path_characters . '*)*' . $valid_url_ending_characters . ')|(?:@' . $valid_url_path_characters . '+\/))';

    // Prepare the domain name pattern. The ICANN seems to be on track towards
    // accepting more diverse top level domains (TLDs), so this pattern has been
    // "future-proofed" to allow for TLDs of length 2-64.
    $domain = '(?:[\p{L}\p{M}\p{N}._+-]+\.)?[\p{L}\p{M}]{2,64}\b';
    // Mail domains differ from the generic domain pattern, specifically: A "."
    // character must be present in the string that follows the @ character.
    $email_domain = '(?:[\p{L}\p{M}\p{N}._+-]+\.)+[\p{L}\p{M}]{2,64}\b';
    $ip = '(?:[0-9]{1,3}\.){3}[0-9]{1,3}';
    $auth = '[\p{L}\p{M}\p{N}:%_+*~#?&=.,/;-]+@';
    $trail = '(' . $valid_url_path . '*)?(\\?' . $valid_url_query_chars . '*' . $valid_url_query_ending_chars . ')?';

    // Match absolute URLs.
    $url_pattern = "(?:$auth)?(?:$domain|$ip)/?(?:$trail)?";
    $pattern = "`((?:$protocols)(?:$url_pattern))`u";
    $tasks[] = [static::class . '::parseFullLinks', $pattern];

    // Match email addresses.
    $url_pattern = "[\p{L}\p{M}\p{N}._+-]{1,254}@(?:$email_domain)";
    $pattern = "`($url_pattern)`u";
    $tasks[] = [static::class . '::parseEmailLinks', $pattern];

    // Match www domains.
    $url_pattern = "www\.(?:$domain)/?(?:$trail)?";
    $pattern = "`($url_pattern)`u";
    $tasks[] = [static::class . '::parsePartialLinks', $pattern];

    // Each type of URL needs to be processed separately. The text is joined and
    // re-split after each task, since all injected HTML tags must be correctly
    // protected before the next task.
    foreach ($tasks as [$callback, $pattern]) {
      // Initialize the HTML comment temporary storage.
      // @see self::escapeComments()
      // @see self::unescapeComments()
      $this->htmlComments = [];

      // HTML comments need to be handled separately, as they may contain HTML
      // markup, especially a '>'. Therefore, remove all comment contents and
      // add them back later.
      $text = is_null($text) ? '' : preg_replace_callback('`<!--(.*?)-->`s', static::class . '::escapeComments', $text);

      // Split at all tags; ensures that no tags or attributes are processed.
      $chunks = is_null($text) ? [''] : preg_split('/(<.+?>)/is', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

      // Do not attempt to convert links into URLs if preg_split() fails.
      if ($chunks !== FALSE) {
        // PHP ensures that the array consists of alternating delimiters and
        // literals, and begins and ends with a literal (inserting NULL as
        // required). Therefore, the first chunk is always text:
        $chunk_type = 'text';
        // If a tag of $ignore_tags is found, it is stored in $open_tag and only
        // removed when the closing tag is found. Until the closing tag is
        // found, no replacements are made.
        $open_tag = '';
        for ($i = 0; $i < count($chunks); $i++) {
          if ($chunk_type == 'text') {
            // Only process this text if there are no unclosed $ignore_tags.
            if ($open_tag == '') {
              // If there is a match, inject a link into this chunk via the
              // callback function contained in $task.
              $chunks[$i] = preg_replace_callback($pattern, $callback, $chunks[$i]);
            }
            // Text chunk is done, so the next chunk must be a tag.
            $chunk_type = 'tag';
          }
          else {
            // Only process this tag if there are no unclosed $ignore_tags.
            if ($open_tag == '') {
              // Check whether this tag is contained in $ignore_tags.
              if (preg_match("`<($ignore_tags)(?:\s|>)`i", $chunks[$i], $matches)) {
                $open_tag = $matches[1];
              }
            }
            // Otherwise, check whether this is the closing tag for $open_tag.
            else {
              if (preg_match("`</$open_tag>`i", $chunks[$i], $matches)) {
                $open_tag = '';
              }
            }
            // Tag chunk is done, so the next chunk must be text.
            $chunk_type = 'text';
          }
        }

        $text = implode($chunks);
      }

      // Revert to the original comment contents.
      $text = $text ? preg_replace_callback('`<!--(.*?)-->`', static::class . '::unescapeComments', $text) : $text;
    }

    // If there is no text at this point, revert to the previous text.
    $text = strlen((string) $text) > 0 ? $text : $saved_text;

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Web page addresses and email addresses turn into links automatically.');
  }

  /**
   * Makes links out of absolute URLs.
   *
   * Callback for preg_replace_callback() within self::process().
   *
   * @param array $match
   *   Regexp match array.
   *
   * @return string
   *   Parsed markup
   */
  protected function parseFullLinks(array $match): string {
    // The $i:th parenthesis in the regexp contains the URL.
    $i = 1;

    $match[$i] = Html::decodeEntities($match[$i]);
    $caption = Html::escape($this->trimUrl($match[$i]));
    $match[$i] = Html::escape($match[$i]);
    return '<a href="' . $match[$i] . '">' . $caption . '</a>';
  }

  /**
   * Makes links out of email addresses.
   *
   * Callback for preg_replace_callback() within self::process().
   *
   * @param array $match
   *   Regexp match array.
   *
   * @return string
   *   Parsed markup
   */
  protected function parseEmailLinks(array $match): string {
    // The $i:th parenthesis in the regexp contains the URL.
    $i = 0;

    $match[$i] = Html::decodeEntities($match[$i]);
    $caption = Html::escape($this->trimUrl($match[$i]));
    $match[$i] = Html::escape($match[$i]);
    return '<a href="mailto:' . $match[$i] . '">' . $caption . '</a>';
  }

  /**
   * Makes links out of domain names starting with "www.".
   *
   * Callback for preg_replace_callback() within self::process().
   *
   * @param array $match
   *   Regexp match array.
   *
   * @return string
   *   Parsed markup
   */
  protected function parsePartialLinks(array $match): string {
    // The $i:th parenthesis in the regexp contains the URL.
    $i = 1;

    $match[$i] = Html::decodeEntities($match[$i]);
    $caption = Html::escape($this->trimUrl($match[$i]));
    $match[$i] = Html::escape($match[$i]);
    return '<a href="http://' . $match[$i] . '">' . $caption . '</a>';
  }

  /**
   * Escapes the contents of HTML comments.
   *
   * Callback for preg_replace_callback() within self::process(). Replaces all
   * HTML comments with a '<!-- [hash] -->' placeholder.
   *
   * @param array<string> $match
   *   An array containing matches to replace from preg_replace_callback(),
   *   whereas $match[1] is expected to contain the content to be filtered.
   *
   * @return string
   *   The escaped comment.
   */
  protected function escapeComments(array $match): string {
    $hash = hash('sha256', $match[1]);
    $this->htmlComments[$hash] = $match[1];
    return "<!-- $hash -->";
  }

  /**
   * Unescapes the contents of HTML comments.
   *
   * Callback for preg_replace_callback() within self::process(). Replaces
   * placeholders with actual comment contents.
   *
   * @param array<string> $match
   *   An array containing matches to replace from preg_replace_callback(),
   *   whereas $match[1] is expected to contain the content to be filtered.
   *
   * @return string
   *   The unescaped comment.
   */
  protected function unescapeComments(array $match): string {
    $hash = $match[1];
    $hash = trim($hash);
    $content = $this->htmlComments[$hash];
    return "<!--$content-->";
  }

  /**
   * Shortens a long URL to a given length ending with an ellipsis.
   *
   * @param string $url
   *   The URL to shorten.
   *
   * @return string
   *   The shortened URL by a 'filter_url_length' setting cutoff.
   */
  protected function trimUrl(string $url): string {
    return Unicode::truncate(
      string: $url,
      max_length: $this->settings['filter_url_length'],
      add_ellipsis: TRUE,
    );
  }

}
