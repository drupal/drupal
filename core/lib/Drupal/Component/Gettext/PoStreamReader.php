<?php

namespace Drupal\Component\Gettext;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Implements Gettext PO stream reader.
 *
 * The PO file format parsing is implemented according to the documentation at
 * http://www.gnu.org/software/gettext/manual/gettext.html#PO-Files
 */
class PoStreamReader implements PoStreamInterface, PoReaderInterface {

  /**
   * Source line number of the stream being parsed.
   *
   * @var int
   */
  protected $lineNumber = 0;

  /**
   * Parser context for the stream reader state machine.
   *
   * Possible contexts are:
   *  - 'COMMENT' (#)
   *  - 'MSGID' (msgid)
   *  - 'MSGID_PLURAL' (msgid_plural)
   *  - 'MSGCTXT' (msgctxt)
   *  - 'MSGSTR' (msgstr or msgstr[])
   *  - 'MSGSTR_ARR' (msgstr_arg)
   *
   * @var string
   */
  protected $context = 'COMMENT';

  /**
   * Current entry being read. Incomplete.
   *
   * @var array
   */
  protected $currentItem = [];

  /**
   * Current plural index for plural translations.
   *
   * @var int
   */
  protected $currentPluralIndex = 0;

  /**
   * URI of the PO stream that is being read.
   *
   * @var string
   */
  protected $uri = '';

  /**
   * Language code for the PO stream being read.
   *
   * @var string
   */
  protected $langcode = NULL;

  /**
   * File handle of the current PO stream.
   *
   * @var resource
   */
  protected $fd;

  /**
   * The PO stream header.
   *
   * @var \Drupal\Component\Gettext\PoHeader
   */
  protected $header;

  /**
   * Object wrapper for the last read source/translation pair.
   *
   * @var \Drupal\Component\Gettext\PoItem
   */
  protected $lastItem;

  /**
   * Indicator of whether the stream reading is finished.
   *
   * @var bool
   */
  protected $finished;

  /**
   * Array of translated error strings recorded on reading this stream so far.
   *
   * @var array
   */
  protected $errors;

  /**
   * {@inheritdoc}
   */
  public function getLangcode() {
    return $this->langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function setLangcode($langcode) {
    $this->langcode = $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->header;
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface::setHeader().
   *
   * Not applicable to stream reading and therefore not implemented.
   */
  public function setHeader(PoHeader $header) {
  }

  /**
   * {@inheritdoc}
   */
  public function getURI() {
    return $this->uri;
  }

  /**
   * {@inheritdoc}
   */
  public function setURI($uri) {
    $this->uri = $uri;
  }

  /**
   * Implements Drupal\Component\Gettext\PoStreamInterface::open().
   *
   * Opens the stream and reads the header. The stream is ready for reading
   * items after.
   *
   * @throws \Exception
   *   If the URI is not yet set.
   */
  public function open() {
    if (!empty($this->uri)) {
      $this->fd = fopen($this->uri, 'rb');
      $this->readHeader();
    }
    else {
      throw new \Exception('Cannot open stream without URI set.');
    }
  }

  /**
   * Implements Drupal\Component\Gettext\PoStreamInterface::close().
   *
   * @throws \Exception
   *   If the stream is not open.
   */
  public function close() {
    if ($this->fd) {
      fclose($this->fd);
    }
    else {
      throw new \Exception('Cannot close stream that is not open.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function readItem() {
    // Clear out the last item.
    $this->lastItem = NULL;

    // Read until finished with the stream or a complete item was identified.
    while (!$this->finished && is_null($this->lastItem)) {
      $this->readLine();
    }

    return $this->lastItem;
  }

  /**
   * Sets the seek position for the current PO stream.
   *
   * @param int $seek
   *   The new seek position to set.
   */
  public function setSeek($seek) {
    fseek($this->fd, $seek);
  }

  /**
   * Gets the pointer position of the current PO stream.
   */
  public function getSeek() {
    return ftell($this->fd);
  }

  /**
   * Read the header from the PO stream.
   *
   * The header is a special case PoItem, using the empty string as source and
   * key-value pairs as translation. We just reuse the item reader logic to
   * read the header.
   */
  private function readHeader() {
    $item = $this->readItem();
    // Handle the case properly when the .po file is empty (0 bytes).
    if (!$item) {
      return;
    }
    $header = new PoHeader();
    $header->setFromString(trim($item->getTranslation()));
    $this->header = $header;
  }

  /**
   * Reads a line from the PO stream and stores data internally.
   *
   * Expands $this->current_item based on new data for the current item. If
   * this line ends the current item, it is saved with setItemFromArray() with
   * data from $this->current_item.
   *
   * An internal state machine is maintained in this reader using
   * $this->context as the reading state. PO items are in between COMMENT
   * states (when items have at least one line or comment in between them) or
   * indicated by MSGSTR or MSGSTR_ARR followed immediately by an MSGID or
   * MSGCTXT (when items closely follow each other).
   *
   * @return
   *   FALSE if an error was logged, NULL otherwise. The errors are considered
   *   non-blocking, so reading can continue, while the errors are collected
   *   for later presentation.
   */
  private function readLine() {
    // Read a line and set the stream finished indicator if it was not
    // possible anymore.
    $line = fgets($this->fd);
    $this->finished = ($line === FALSE);

    if (!$this->finished) {

      if ($this->lineNumber == 0) {
        // The first line might come with a UTF-8 BOM, which should be removed.
        $line = str_replace("\xEF\xBB\xBF", '', $line);
        // Current plurality for 'msgstr[]'.
        $this->currentPluralIndex = 0;
      }

      // Track the line number for error reporting.
      $this->lineNumber++;

      // Initialize common values for error logging.
      $log_vars = [
        '%uri' => $this->getURI(),
        '%line' => $this->lineNumber,
      ];

      // Trim away the linefeed. \\n might appear at the end of the string if
      // another line continuing the same string follows. We can remove that.
      $line = trim(strtr($line, ["\\\n" => ""]));

      if (!strncmp('#', $line, 1)) {
        // Lines starting with '#' are comments.

        if ($this->context == 'COMMENT') {
          // Already in comment context, add to current comment.
          $this->currentItem['#'][] = substr($line, 1);
        }
        elseif (($this->context == 'MSGSTR') || ($this->context == 'MSGSTR_ARR')) {
          // We are currently in string context, save current item.
          $this->setItemFromArray($this->currentItem);

          // Start a new entry for the comment.
          $this->currentItem = [];
          $this->currentItem['#'][] = substr($line, 1);

          $this->context = 'COMMENT';
          return;
        }
        else {
          // A comment following any other context is a syntax error.
          $this->errors[] = new FormattableMarkup('The translation stream %uri contains an error: "msgstr" was expected but not found on line %line.', $log_vars);
          return FALSE;
        }
        return;
      }
      elseif (!strncmp('msgid_plural', $line, 12)) {
        // A plural form for the current source string.

        if ($this->context != 'MSGID') {
          // A plural form can only be added to an msgid directly.
          $this->errors[] = new FormattableMarkup('The translation stream %uri contains an error: "msgid_plural" was expected but not found on line %line.', $log_vars);
          return FALSE;
        }

        // Remove 'msgid_plural' and trim away whitespace.
        $line = trim(substr($line, 12));

        // Only the plural source string is left, parse it.
        $quoted = $this->parseQuoted($line);
        if ($quoted === FALSE) {
          // The plural form must be wrapped in quotes.
          $this->errors[] = new FormattableMarkup('The translation stream %uri contains a syntax error on line %line.', $log_vars);
          return FALSE;
        }

        // Append the plural source to the current entry.
        if (is_string($this->currentItem['msgid'])) {
          // The first value was stored as string. Now we know the context is
          // plural, it is converted to array.
          $this->currentItem['msgid'] = [$this->currentItem['msgid']];
        }
        $this->currentItem['msgid'][] = $quoted;

        $this->context = 'MSGID_PLURAL';
        return;
      }
      elseif (!strncmp('msgid', $line, 5)) {
        // Starting a new message.

        if (($this->context == 'MSGSTR') || ($this->context == 'MSGSTR_ARR')) {
          // We are currently in string context, save current item.
          $this->setItemFromArray($this->currentItem);

          // Start a new context for the msgid.
          $this->currentItem = [];
        }
        elseif ($this->context == 'MSGID') {
          // We are currently already in the context, meaning we passed an id with no data.
          $this->errors[] = new FormattableMarkup('The translation stream %uri contains an error: "msgid" is unexpected on line %line.', $log_vars);
          return FALSE;
        }

        // Remove 'msgid' and trim away whitespace.
        $line = trim(substr($line, 5));

        // Only the message id string is left, parse it.
        $quoted = $this->parseQuoted($line);
        if ($quoted === FALSE) {
          // The message id must be wrapped in quotes.
          $this->errors[] = new FormattableMarkup('The translation stream %uri contains an error: invalid format for "msgid" on line %line.', $log_vars, $log_vars);
          return FALSE;
        }

        $this->currentItem['msgid'] = $quoted;
        $this->context = 'MSGID';
        return;
      }
      elseif (!strncmp('msgctxt', $line, 7)) {
        // Starting a new context.

        if (($this->context == 'MSGSTR') || ($this->context == 'MSGSTR_ARR')) {
          // We are currently in string context, save current item.
          $this->setItemFromArray($this->currentItem);
          $this->currentItem = [];
        }
        elseif (!empty($this->currentItem['msgctxt'])) {
          // A context cannot apply to another context.
          $this->errors[] = new FormattableMarkup('The translation stream %uri contains an error: "msgctxt" is unexpected on line %line.', $log_vars);
          return FALSE;
        }

        // Remove 'msgctxt' and trim away whitespaces.
        $line = trim(substr($line, 7));

        // Only the msgctxt string is left, parse it.
        $quoted = $this->parseQuoted($line);
        if ($quoted === FALSE) {
          // The context string must be quoted.
          $this->errors[] = new FormattableMarkup('The translation stream %uri contains an error: invalid format for "msgctxt" on line %line.', $log_vars);
          return FALSE;
        }

        $this->currentItem['msgctxt'] = $quoted;

        $this->context = 'MSGCTXT';
        return;
      }
      elseif (!strncmp('msgstr[', $line, 7)) {
        // A message string for a specific plurality.

        if (($this->context != 'MSGID') &&
            ($this->context != 'MSGCTXT') &&
            ($this->context != 'MSGID_PLURAL') &&
            ($this->context != 'MSGSTR_ARR')) {
          // Plural message strings must come after msgid, msgctxt,
          // msgid_plural, or other msgstr[] entries.
          $this->errors[] = new FormattableMarkup('The translation stream %uri contains an error: "msgstr[]" is unexpected on line %line.', $log_vars);
          return FALSE;
        }

        // Ensure the plurality is terminated.
        if (strpos($line, ']') === FALSE) {
          $this->errors[] = new FormattableMarkup('The translation stream %uri contains an error: invalid format for "msgstr[]" on line %line.', $log_vars);
          return FALSE;
        }

        // Extract the plurality.
        $frombracket = strstr($line, '[');
        $this->currentPluralIndex = substr($frombracket, 1, strpos($frombracket, ']') - 1);

        // Skip to the next whitespace and trim away any further whitespace,
        // bringing $line to the message text only.
        $line = trim(strstr($line, " "));

        $quoted = $this->parseQuoted($line);
        if ($quoted === FALSE) {
          // The string must be quoted.
          $this->errors[] = new FormattableMarkup('The translation stream %uri contains an error: invalid format for "msgstr[]" on line %line.', $log_vars);
          return FALSE;
        }
        if (!isset($this->currentItem['msgstr']) || !is_array($this->currentItem['msgstr'])) {
          $this->currentItem['msgstr'] = [];
        }

        $this->currentItem['msgstr'][$this->currentPluralIndex] = $quoted;

        $this->context = 'MSGSTR_ARR';
        return;
      }
      elseif (!strncmp("msgstr", $line, 6)) {
        // A string pair for an msgid (with optional context).

        if (($this->context != 'MSGID') && ($this->context != 'MSGCTXT')) {
          // Strings are only valid within an id or context scope.
          $this->errors[] = new FormattableMarkup('The translation stream %uri contains an error: "msgstr" is unexpected on line %line.', $log_vars);
          return FALSE;
        }

        // Remove 'msgstr' and trim away away whitespaces.
        $line = trim(substr($line, 6));

        // Only the msgstr string is left, parse it.
        $quoted = $this->parseQuoted($line);
        if ($quoted === FALSE) {
          // The string must be quoted.
          $this->errors[] = new FormattableMarkup('The translation stream %uri contains an error: invalid format for "msgstr" on line %line.', $log_vars);
          return FALSE;
        }

        $this->currentItem['msgstr'] = $quoted;

        $this->context = 'MSGSTR';
        return;
      }
      elseif ($line != '') {
        // Anything that is not a token may be a continuation of a previous token.

        $quoted = $this->parseQuoted($line);
        if ($quoted === FALSE) {
          // This string must be quoted.
          $this->errors[] = new FormattableMarkup('The translation stream %uri contains an error: string continuation expected on line %line.', $log_vars);
          return FALSE;
        }

        // Append the string to the current item.
        if (($this->context == 'MSGID') || ($this->context == 'MSGID_PLURAL')) {
          if (is_array($this->currentItem['msgid'])) {
            // Add string to last array element for plural sources.
            $last_index = count($this->currentItem['msgid']) - 1;
            $this->currentItem['msgid'][$last_index] .= $quoted;
          }
          else {
            // Singular source, just append the string.
            $this->currentItem['msgid'] .= $quoted;
          }
        }
        elseif ($this->context == 'MSGCTXT') {
          // Multiline context name.
          $this->currentItem['msgctxt'] .= $quoted;
        }
        elseif ($this->context == 'MSGSTR') {
          // Multiline translation string.
          $this->currentItem['msgstr'] .= $quoted;
        }
        elseif ($this->context == 'MSGSTR_ARR') {
          // Multiline plural translation string.
          $this->currentItem['msgstr'][$this->currentPluralIndex] .= $quoted;
        }
        else {
          // No valid context to append to.
          $this->errors[] = new FormattableMarkup('The translation stream %uri contains an error: unexpected string on line %line.', $log_vars);
          return FALSE;
        }
        return;
      }
    }

    // Empty line read or EOF of PO stream, close out the last entry.
    if (($this->context == 'MSGSTR') || ($this->context == 'MSGSTR_ARR')) {
      $this->setItemFromArray($this->currentItem);
      $this->currentItem = [];
    }
    elseif ($this->context != 'COMMENT') {
      $this->errors[] = new FormattableMarkup('The translation stream %uri ended unexpectedly at line %line.', $log_vars);
      return FALSE;
    }
  }

  /**
   * Store the parsed values as a PoItem object.
   */
  public function setItemFromArray($value) {
    $plural = FALSE;

    $comments = '';
    if (isset($value['#'])) {
      $comments = $this->shortenComments($value['#']);
    }

    if (is_array($value['msgstr'])) {
      // Sort plural variants by their form index.
      ksort($value['msgstr']);
      $plural = TRUE;
    }

    $item = new PoItem();
    $item->setContext($value['msgctxt'] ?? '');
    $item->setSource($value['msgid']);
    $item->setTranslation($value['msgstr']);
    $item->setPlural($plural);
    $item->setComment($comments);
    $item->setLangcode($this->langcode);

    $this->lastItem = $item;

    $this->context = 'COMMENT';
  }

  /**
   * Parses a string in quotes.
   *
   * @param $string
   *   A string specified with enclosing quotes.
   *
   * @return
   *   The string parsed from inside the quotes.
   */
  public function parseQuoted($string) {
    if (substr($string, 0, 1) != substr($string, -1, 1)) {
      // Start and end quotes must be the same.
      return FALSE;
    }
    $quote = substr($string, 0, 1);
    $string = substr($string, 1, -1);
    if ($quote == '"') {
      // Double quotes: strip slashes.
      return stripcslashes($string);
    }
    elseif ($quote == "'") {
      // Simple quote: return as-is.
      return $string;
    }
    else {
      // Unrecognized quote.
      return FALSE;
    }
  }

  /**
   * Generates a short, one-string version of the passed comment array.
   *
   * @param $comment
   *   An array of strings containing a comment.
   *
   * @return
   *   Short one-string version of the comment.
   */
  private function shortenComments($comment) {
    $comm = '';
    while (count($comment)) {
      $test = $comm . substr(array_shift($comment), 1) . ', ';
      if (strlen($comm) < 130) {
        $comm = $test;
      }
      else {
        break;
      }
    }
    return trim(substr($comm, 0, -2));
  }

}
