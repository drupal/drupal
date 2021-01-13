<?php

namespace Drupal\help_topics_twig_tester;

use Drupal\Core\Template\TwigNodeTrans;
use Twig\Environment;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Node\SetNode;
use Twig\Node\TextNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\NodeVisitor\AbstractNodeVisitor;

/**
 * Defines a Twig node visitor for testing help topics.
<<<<<<< 071b42e1ea21830eee33997671b2850ce844f52e
 *
 * See static::setStateValue() for information on the types of processing
 * this class can do.
=======
>>>>>>> Non-working initial patch from the issue
 */
class HelpTestTwigNodeVisitor extends AbstractNodeVisitor {

  /**
<<<<<<< 071b42e1ea21830eee33997671b2850ce844f52e
   * Delimiter placed around single translated chunks.
   */
  public const DELIMITER = 'Not Likely To Be Inside A Template';

  /**
   * Name used in \Drupal::state() for saving state information.
   */
  protected const STATE_NAME = 'help_test_twig_node_visitor';

  /**
=======
>>>>>>> Non-working initial patch from the issue
   * {@inheritdoc}
   */
  protected function doEnterNode(Node $node, Environment $env) {
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  protected function doLeaveNode(Node $node, Environment $env) {
<<<<<<< 071b42e1ea21830eee33997671b2850ce844f52e
    $processing = static::getState();
=======
    $processing = help_topics_twig_tester_get_values();
>>>>>>> Non-working initial patch from the issue
    if (!$processing['type']) {
      return $node;
    }

    // For all processing types, we want to remove variables, set statements,
    // and assorted Twig expression calls (if, do, etc.).
    if ($node instanceof SetNode || $node instanceof PrintNode ||
       $node instanceof AbstractExpression) {
      return NULL;
    }

    if ($node instanceof TwigNodeTrans) {
<<<<<<< 071b42e1ea21830eee33997671b2850ce844f52e
      // Count the number of translated chunks.
      $this_chunk = $processing['chunk_count'] + 1;
      static::setStateValue('chunk_count', $this_chunk);
      if ($this_chunk > $processing['max_chunk']) {
        static::setStateValue('max_chunk', $this_chunk);
      }

=======
>>>>>>> Non-working initial patch from the issue
      if ($processing['type'] == 'remove_translated') {
        // Remove all translated text.
        return NULL;
      }
<<<<<<< 071b42e1ea21830eee33997671b2850ce844f52e
      elseif ($processing['type'] == 'replace_translated') {
        // Replace with a dummy string.
        $node = new TextNode('dummy', 0);
      }
      elseif ($processing['type'] == 'translated_chunk') {
        // Return the text only if it's the next chunk we're supposed to return.
        // Add a wrapper, because non-translated nodes will still be returned.
        if ($this_chunk == $processing['return_chunk']) {
          return new TextNode(static::DELIMITER . $this->extractText($node) . static::DELIMITER, 0);
        }
        else {
          return NULL;
        }
=======
      else if ($processing['type'] == 'replace_translated') {
        // Replace with a dummy string.
        $node = new TextNode(['data' => 'dummy']);
      }
      else if ($processing['type'] == 'translated_chunk') {
        // Return the text only if it's the next chunk we're supposed to return.
        $this_chunk = $processing['chunk_count'] + 1;
        help_topics_twig_tester_set_value('chunk_count', $this_chunk);
        if ($this_chunk > $processing['max_chunk']) {
          help_topics_twig_tester_set_value('max_chunk', $this_chunk);
        }

        if ($this_chunk == $processing['return_chunk']) {
          return $node->getNode('body');
        }

        return NULL;
>>>>>>> Non-working initial patch from the issue
      }
    }

    if ($processing['type'] == 'remove_translated' && $node instanceof TextNode) {
      // For this processing, we also want to remove all HTML tags and
      // whitespace from TextNodes.
      $text = $node->getAttribute('data');
      $text = strip_tags($text);
      $text = preg_replace('|\s+|', '', $text);
<<<<<<< 071b42e1ea21830eee33997671b2850ce844f52e
      return new TextNode($text, 0);
    }

    return $node;
  }
=======
      return new TextNode(['data' => $text]);
    }

    return $node;
 }
>>>>>>> Non-working initial patch from the issue

  /**
   * {@inheritdoc}
   */
  public function getPriority() {
    return -100;
  }

<<<<<<< 071b42e1ea21830eee33997671b2850ce844f52e
  /**
   * Extracts the text from a translated text object.
   *
   * @param \Drupal\Core\Template\TwigNodeTrans $node
   *   Translated text node.
   *
   * @return string
   *   Text in the node.
   */
  protected function extractText(TwigNodeTrans $node) {
    // Extract the singular/body and optional plural text from the
    // TwigNodeTrans object.
    $bodies = $node->getNode('body');
    if (!count($bodies)) {
      $bodies = [$bodies];
    }
    if ($node->hasNode('plural')) {
      $plural = $node->getNode('plural');
      if (!count($plural)) {
        $bodies[] = $plural;
      }
      else {
        foreach ($plural as $item) {
          $bodies[] = $item;
        }
      }
    }

    // Extract the text from each component of the singular/plural strings.
    $text = '';
    foreach ($bodies as $body) {
      if ($body->hasAttribute('data')) {
        $text .= $body->getAttribute('data');
      }
    }
    return trim($text);
  }

  /**
   * Returns the state information.
   *
   * @return array
   *   The state information.
   */
  public static function getState() {
    return \Drupal::state()->get(static::STATE_NAME, ['type' => 0]);
  }

  /**
   * Sets state information.
   *
   * @param string $key
   *   Key to set. Possible keys:
   *   - type: Type of processing to do. Values:
   *     - 0: No processing.
   *     - remove_translated: Remove all translated text, HTML tags, and
   *       whitespace.
   *     - replace_translated: Replace all translated text with dummy text.
   *     - translated_chunk: Remove all translated text except one designated
   *       chunk (see return_chunk below).
   *     - bare_body (or any other non-zero value): Remove variables, set
   *       statements, and Twig programming, but leave everything else intact.
   *   - chunk_count: Current index of translated chunks. Reset to -1 before
   *     each rendering run. (Used internally by this class.)
   *   - max_chunk: Maximum index of translated chunks. Reset to -1 before
   *     each rendering run.
   *   - return_chunk: Chunk index to keep intact for translated_chunk
   *     processing. All others are removed.
   * @param $value
   *   Value to set for $key.
   */
  public static function setStateValue(string $key, $value) {
    $state = \Drupal::state();
    $values = $state->get(static::STATE_NAME, ['type' => 0]);
    $values[$key] = $value;
    $state->set(static::STATE_NAME, $values);
  }

=======
>>>>>>> Non-working initial patch from the issue
}
