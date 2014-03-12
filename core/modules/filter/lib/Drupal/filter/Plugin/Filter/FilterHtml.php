<?php

/**
 * @file
 * Contains \Drupal\filter\Plugin\Filter\FilterHtml.
 */

namespace Drupal\filter\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to limit allowed HTML tags.
 *
 * @Filter(
 *   id = "filter_html",
 *   title = @Translation("Limit allowed HTML tags"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR,
 *   settings = {
 *     "allowed_html" = "<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd> <h4> <h5> <h6>",
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
  public function settingsForm(array $form, array &$form_state) {
    $form['allowed_html'] = array(
      '#type' => 'textfield',
      '#title' => t('Allowed HTML tags'),
      '#default_value' => $this->settings['allowed_html'],
      '#maxlength' => 1024,
      '#description' => t('A list of HTML tags that can be used. JavaScript event attributes, JavaScript URLs, and CSS are always stripped.'),
      '#attached' => array(
        'library' => array(
          'filter/drupal.filter.filter_html.admin',
        ),
      ),
    );
    $form['filter_html_help'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display basic HTML help in long filter tips'),
      '#default_value' => $this->settings['filter_html_help'],
    );
    $form['filter_html_nofollow'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add rel="nofollow" to all links'),
      '#default_value' => $this->settings['filter_html_nofollow'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode, $cache, $cache_id) {
    return _filter_html($text, $this);
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
    $output = t('Allowed HTML tags: @tags', array('@tags' => $allowed_html));
    if (!$long) {
      return $output;
    }

    $output = '<p>' . $output . '</p>';
    if (!$this->settings['filter_html_help']) {
      return $output;
    }

    $output .= '<p>' . t('This site allows HTML content. While learning all of HTML may feel intimidating, learning how to use a very small number of the most basic HTML "tags" is very easy. This table provides examples for each tag that is enabled on this site.') . '</p>';
    $output .= '<p>' . t('For more information see W3C\'s <a href="@html-specifications">HTML Specifications</a> or use your favorite search engine to find other sites that explain HTML.', array('@html-specifications' => 'http://www.w3.org/TR/html/')) . '</p>';
    $tips = array(
      'a' => array(t('Anchors are used to make links to other pages.'), '<a href="' . $base_url . '">' . check_plain(\Drupal::config('system.site')->get('name')) . '</a>'),
      'br' => array(t('By default line break tags are automatically added, so use this tag to add additional ones. Use of this tag is different because it is not used with an open/close pair like all the others. Use the extra " /" inside the tag to maintain XHTML 1.0 compatibility'), t('Text with <br />line break')),
      'p' => array(t('By default paragraph tags are automatically added, so use this tag to add additional ones.'), '<p>' . t('Paragraph one.') . '</p> <p>' . t('Paragraph two.') . '</p>'),
      'strong' => array(t('Strong', array(), array('context' => 'Font weight')), '<strong>' . t('Strong', array(), array('context' => 'Font weight')) . '</strong>'),
      'em' => array(t('Emphasized'), '<em>' . t('Emphasized') . '</em>'),
      'cite' => array(t('Cited'), '<cite>' . t('Cited') . '</cite>'),
      'code' => array(t('Coded text used to show programming source code'), '<code>' . t('Coded') . '</code>'),
      'b' => array(t('Bolded'), '<b>' . t('Bolded') . '</b>'),
      'u' => array(t('Underlined'), '<u>' . t('Underlined') . '</u>'),
      'i' => array(t('Italicized'), '<i>' . t('Italicized') . '</i>'),
      'sup' => array(t('Superscripted'), t('<sup>Super</sup>scripted')),
      'sub' => array(t('Subscripted'), t('<sub>Sub</sub>scripted')),
      'pre' => array(t('Preformatted'), '<pre>' . t('Preformatted') . '</pre>'),
      'abbr' => array(t('Abbreviation'), t('<abbr title="Abbreviation">Abbrev.</abbr>')),
      'acronym' => array(t('Acronym'), t('<acronym title="Three-Letter Acronym">TLA</acronym>')),
      'blockquote' => array(t('Block quoted'), '<blockquote>' . t('Block quoted') . '</blockquote>'),
      'q' => array(t('Quoted inline'), '<q>' . t('Quoted inline') . '</q>'),
      // Assumes and describes tr, td, th.
      'table' => array(t('Table'), '<table> <tr><th>' . t('Table header') . '</th></tr> <tr><td>' . t('Table cell') . '</td></tr> </table>'),
      'tr' => NULL, 'td' => NULL, 'th' => NULL,
      'del' => array(t('Deleted'), '<del>' . t('Deleted') . '</del>'),
      'ins' => array(t('Inserted'), '<ins>' . t('Inserted') . '</ins>'),
       // Assumes and describes li.
      'ol' => array(t('Ordered list - use the &lt;li&gt; to begin each list item'), '<ol> <li>' . t('First item') . '</li> <li>' . t('Second item') . '</li> </ol>'),
      'ul' => array(t('Unordered list - use the &lt;li&gt; to begin each list item'), '<ul> <li>' . t('First item') . '</li> <li>' . t('Second item') . '</li> </ul>'),
      'li' => NULL,
      // Assumes and describes dt and dd.
      'dl' => array(t('Definition lists are similar to other HTML lists. &lt;dl&gt; begins the definition list, &lt;dt&gt; begins the definition term and &lt;dd&gt; begins the definition description.'), '<dl> <dt>' . t('First term') . '</dt> <dd>' . t('First definition') . '</dd> <dt>' . t('Second term') . '</dt> <dd>' . t('Second definition') . '</dd> </dl>'),
      'dt' => NULL, 'dd' => NULL,
      'h1' => array(t('Heading'), '<h1>' . t('Title') . '</h1>'),
      'h2' => array(t('Heading'), '<h2>' . t('Subtitle') . '</h2>'),
      'h3' => array(t('Heading'), '<h3>' . t('Subtitle three') . '</h3>'),
      'h4' => array(t('Heading'), '<h4>' . t('Subtitle four') . '</h4>'),
      'h5' => array(t('Heading'), '<h5>' . t('Subtitle five') . '</h5>'),
      'h6' => array(t('Heading'), '<h6>' . t('Subtitle six') . '</h6>')
    );
    $header = array(t('Tag Description'), t('You Type'), t('You Get'));
    preg_match_all('/<([a-z0-9]+)[^a-z0-9]/i', $allowed_html, $out);
    foreach ($out[1] as $tag) {
      if (!empty($tips[$tag])) {
        $rows[] = array(
          array('data' => $tips[$tag][0], 'class' => array('description')),
          array('data' => '<code>' . check_plain($tips[$tag][1]) . '</code>', 'class' => array('type')),
          array('data' => $tips[$tag][1], 'class' => array('get'))
        );
      }
      else {
        $rows[] = array(
          array('data' => t('No help provided for tag %tag.', array('%tag' => $tag)), 'class' => array('description'), 'colspan' => 3),
        );
      }
    }
    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
    $output .= drupal_render($table);

    $output .= '<p>' . t('Most unusual characters can be directly entered without any problems.') . '</p>';
    $output .= '<p>' . t('If you do encounter problems, try using HTML character entities. A common example looks like &amp;amp; for an ampersand &amp; character. For a full list of entities see HTML\'s <a href="@html-entities">entities</a> page. Some of the available characters include:', array('@html-entities' => 'http://www.w3.org/TR/html4/sgml/entities.html')) . '</p>';

    $entities = array(
      array(t('Ampersand'), '&amp;'),
      array(t('Greater than'), '&gt;'),
      array(t('Less than'), '&lt;'),
      array(t('Quotation mark'), '&quot;'),
    );
    $header = array(t('Character Description'), t('You Type'), t('You Get'));
    unset($rows);
    foreach ($entities as $entity) {
      $rows[] = array(
        array('data' => $entity[0], 'class' => array('description')),
        array('data' => '<code>' . check_plain($entity[1]) . '</code>', 'class' => array('type')),
        array('data' => $entity[1], 'class' => array('get'))
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
