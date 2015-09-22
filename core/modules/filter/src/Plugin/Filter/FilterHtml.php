<?php

/**
 * @file
 * Contains \Drupal\filter\Plugin\Filter\FilterHtml.
 */

namespace Drupal\filter\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to limit allowed HTML tags.
 *
 * @Filter(
 *   id = "filter_html",
 *   title = @Translation("Limit allowed HTML tags"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR,
 *   settings = {
 *     "allowed_html" = "<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd> <h2> <h3> <h4> <h5> <h6>",
 *     "filter_html_help" = TRUE,
 *     "filter_html_nofollow" = FALSE
 *   },
 *   weight = -10
 * )
 */
class FilterHtml extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['allowed_html'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Allowed HTML tags'),
      '#default_value' => $this->settings['allowed_html'],
      '#maxlength' => 1024,
      '#description' => $this->t('A list of HTML tags that can be used. JavaScript event attributes, JavaScript URLs, and CSS are always stripped.'),
      '#attached' => array(
        'library' => array(
          'filter/drupal.filter.filter_html.admin',
        ),
      ),
    );
    $form['filter_html_help'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display basic HTML help in long filter tips'),
      '#default_value' => $this->settings['filter_html_help'],
    );
    $form['filter_html_nofollow'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Add rel="nofollow" to all links'),
      '#default_value' => $this->settings['filter_html_nofollow'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult(_filter_html($text, $this));
  }

  /**
   * {@inheritdoc}
   */
  public function getHTMLRestrictions() {
    $restrictions = array('allowed' => array());
    $tags = preg_split('/\s+|<|>/', $this->settings['allowed_html'], -1, PREG_SPLIT_NO_EMPTY);
    // List the allowed HTML tags.
    foreach ($tags as $tag) {
      $restrictions['allowed'][$tag] = TRUE;
    }
    // The 'style' and 'on*' ('onClick' etc.) attributes are always forbidden.
    $restrictions['allowed']['*'] = array('style' => FALSE, 'on*' => FALSE);
    return $restrictions;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    global $base_url;

    if (!($allowed_html = $this->settings['allowed_html'])) {
      return;
    }
    $output = $this->t('Allowed HTML tags: @tags', array('@tags' => $allowed_html));
    if (!$long) {
      return $output;
    }

    $output = '<p>' . $output . '</p>';
    if (!$this->settings['filter_html_help']) {
      return $output;
    }

    $output .= '<p>' . $this->t('This site allows HTML content. While learning all of HTML may feel intimidating, learning how to use a very small number of the most basic HTML "tags" is very easy. This table provides examples for each tag that is enabled on this site.') . '</p>';
    $output .= '<p>' . $this->t('For more information see W3C\'s <a href=":html-specifications">HTML Specifications</a> or use your favorite search engine to find other sites that explain HTML.', array(':html-specifications' => 'http://www.w3.org/TR/html/')) . '</p>';
    $tips = array(
      'a' => array($this->t('Anchors are used to make links to other pages.'), '<a href="' . $base_url . '">' . Html::escape(\Drupal::config('system.site')->get('name')) . '</a>'),
      'br' => array($this->t('By default line break tags are automatically added, so use this tag to add additional ones. Use of this tag is different because it is not used with an open/close pair like all the others. Use the extra " /" inside the tag to maintain XHTML 1.0 compatibility'), $this->t('Text with <br />line break')),
      'p' => array($this->t('By default paragraph tags are automatically added, so use this tag to add additional ones.'), '<p>' . $this->t('Paragraph one.') . '</p> <p>' . $this->t('Paragraph two.') . '</p>'),
      'strong' => array($this->t('Strong', array(), array('context' => 'Font weight')), '<strong>' . $this->t('Strong', array(), array('context' => 'Font weight')) . '</strong>'),
      'em' => array($this->t('Emphasized'), '<em>' . $this->t('Emphasized') . '</em>'),
      'cite' => array($this->t('Cited'), '<cite>' . $this->t('Cited') . '</cite>'),
      'code' => array($this->t('Coded text used to show programming source code'), '<code>' . $this->t('Coded') . '</code>'),
      'b' => array($this->t('Bolded'), '<b>' . $this->t('Bolded') . '</b>'),
      'u' => array($this->t('Underlined'), '<u>' . $this->t('Underlined') . '</u>'),
      'i' => array($this->t('Italicized'), '<i>' . $this->t('Italicized') . '</i>'),
      'sup' => array($this->t('Superscripted'), $this->t('<sup>Super</sup>scripted')),
      'sub' => array($this->t('Subscripted'), $this->t('<sub>Sub</sub>scripted')),
      'pre' => array($this->t('Preformatted'), '<pre>' . $this->t('Preformatted') . '</pre>'),
      'abbr' => array($this->t('Abbreviation'), $this->t('<abbr title="Abbreviation">Abbrev.</abbr>')),
      'acronym' => array($this->t('Acronym'), $this->t('<acronym title="Three-Letter Acronym">TLA</acronym>')),
      'blockquote' => array($this->t('Block quoted'), '<blockquote>' . $this->t('Block quoted') . '</blockquote>'),
      'q' => array($this->t('Quoted inline'), '<q>' . $this->t('Quoted inline') . '</q>'),
      // Assumes and describes tr, td, th.
      'table' => array($this->t('Table'), '<table> <tr><th>' . $this->t('Table header') . '</th></tr> <tr><td>' . $this->t('Table cell') . '</td></tr> </table>'),
      'tr' => NULL, 'td' => NULL, 'th' => NULL,
      'del' => array($this->t('Deleted'), '<del>' . $this->t('Deleted') . '</del>'),
      'ins' => array($this->t('Inserted'), '<ins>' . $this->t('Inserted') . '</ins>'),
       // Assumes and describes li.
      'ol' => array($this->t('Ordered list - use the &lt;li&gt; to begin each list item'), '<ol> <li>' . $this->t('First item') . '</li> <li>' . $this->t('Second item') . '</li> </ol>'),
      'ul' => array($this->t('Unordered list - use the &lt;li&gt; to begin each list item'), '<ul> <li>' . $this->t('First item') . '</li> <li>' . $this->t('Second item') . '</li> </ul>'),
      'li' => NULL,
      // Assumes and describes dt and dd.
      'dl' => array($this->t('Definition lists are similar to other HTML lists. &lt;dl&gt; begins the definition list, &lt;dt&gt; begins the definition term and &lt;dd&gt; begins the definition description.'), '<dl> <dt>' . $this->t('First term') . '</dt> <dd>' . $this->t('First definition') . '</dd> <dt>' . $this->t('Second term') . '</dt> <dd>' . $this->t('Second definition') . '</dd> </dl>'),
      'dt' => NULL, 'dd' => NULL,
      'h1' => array($this->t('Heading'), '<h1>' . $this->t('Title') . '</h1>'),
      'h2' => array($this->t('Heading'), '<h2>' . $this->t('Subtitle') . '</h2>'),
      'h3' => array($this->t('Heading'), '<h3>' . $this->t('Subtitle three') . '</h3>'),
      'h4' => array($this->t('Heading'), '<h4>' . $this->t('Subtitle four') . '</h4>'),
      'h5' => array($this->t('Heading'), '<h5>' . $this->t('Subtitle five') . '</h5>'),
      'h6' => array($this->t('Heading'), '<h6>' . $this->t('Subtitle six') . '</h6>')
    );
    $header = array($this->t('Tag Description'), $this->t('You Type'), $this->t('You Get'));
    preg_match_all('/<([a-z0-9]+)[^a-z0-9]/i', $allowed_html, $out);
    foreach ($out[1] as $tag) {
      if (!empty($tips[$tag])) {
        $rows[] = array(
          array('data' => $tips[$tag][0], 'class' => array('description')),
          // The markup must be escaped because this is the example code for the
          // user.
          array('data' =>
            array(
              '#prefix' => '<code>',
              '#plain_text' => $tips[$tag][1],
              '#suffix' => '</code>'
            ),
            'class' => array('type')),
          // The markup must not be escaped because this is the example output
          // for the user.
          array('data' =>
            array('#markup' => $tips[$tag][1]),
            'class' => array('get'),
          ),
        );
      }
      else {
        $rows[] = array(
          array('data' => $this->t('No help provided for tag %tag.', array('%tag' => $tag)), 'class' => array('description'), 'colspan' => 3),
        );
      }
    }
    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
    $output .= drupal_render($table);

    $output .= '<p>' . $this->t('Most unusual characters can be directly entered without any problems.') . '</p>';
    $output .= '<p>' . $this->t('If you do encounter problems, try using HTML character entities. A common example looks like &amp;amp; for an ampersand &amp; character. For a full list of entities see HTML\'s <a href=":html-entities">entities</a> page. Some of the available characters include:', array(':html-entities' => 'http://www.w3.org/TR/html4/sgml/entities.html')) . '</p>';

    $entities = array(
      array($this->t('Ampersand'), '&amp;'),
      array($this->t('Greater than'), '&gt;'),
      array($this->t('Less than'), '&lt;'),
      array($this->t('Quotation mark'), '&quot;'),
    );
    $header = array($this->t('Character Description'), $this->t('You Type'), $this->t('You Get'));
    unset($rows);
    foreach ($entities as $entity) {
      $rows[] = array(
        array('data' => $entity[0], 'class' => array('description')),
        // The markup must be escaped because this is the example code for the
        // user.
        array(
          'data' => array(
            '#prefix' => '<code>',
            '#plain_text' => $entity[1],
            '#suffix' => '</code>',
          ),
          'class' => array('type'),
        ),
        // The markup must not be escaped because this is the example output
        // for the user.
        array(
          'data' => array('#markup' => $entity[1]),
          'class' => array('get'),
        ),
      );
    }
    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
    $output .= drupal_render($table);
    return $output;
  }

}
