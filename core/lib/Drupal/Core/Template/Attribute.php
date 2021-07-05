<?php

namespace Drupal\Core\Template;

use Drupal\Component\Attribute\AttributeCollection;

@trigger_error('\Drupal\Core\Template\Attribute is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Component\Attribute\AttributeCollection instead. See https://www.drupal.org/node/3070485', E_USER_DEPRECATED);

/**
 * Collects, sanitizes, and renders HTML attributes.
 *
 * To use, optionally pass in an associative array of defined attributes, or
 * add attributes using array syntax. For example:
 * @code
 *  $attributes = new Attribute(array('id' => 'socks'));
 *  $attributes['class'] = array('black-cat', 'white-cat');
 *  $attributes['class'][] = 'black-white-cat';
 *  echo '<cat' . $attributes . '>';
 *  // Produces <cat id="socks" class="black-cat white-cat black-white-cat">
 * @endcode
 *
 * $attributes always prints out all the attributes. For example:
 * @code
 *  $attributes = new Attribute(array('id' => 'socks'));
 *  $attributes['class'] = array('black-cat', 'white-cat');
 *  $attributes['class'][] = 'black-white-cat';
 *  echo '<cat class="cat ' . $attributes['class'] . '"' . $attributes . '>';
 *  // Produces <cat class="cat black-cat white-cat black-white-cat" id="socks" class="cat black-cat white-cat black-white-cat">
 * @endcode
 *
 * When printing out individual attributes to customize them within a Twig
 * template, use the "without" filter to prevent attributes that have already
 * been printed from being printed again. For example:
 * @code
 * <cat class="{{ attributes.class }} my-custom-class"{{ attributes|without('class') }}>
 * @endcode
 * Produces:
 * @code
 * <cat class="cat black-cat white-cat black-white-cat my-custom-class" id="socks">
 * @endcode
 *
 * The attribute keys and values are automatically escaped for output with
 * Html::escape(). No protocol filtering is applied, so when using user-entered
 * input as a value for an attribute that expects a URI (href, src, ...),
 * UrlHelper::stripDangerousProtocols() should be used to ensure dangerous
 * protocols (such as 'javascript:') are removed. For example:
 * @code
 *  $path = 'javascript:alert("xss");';
 *  $path = UrlHelper::stripDangerousProtocols($path);
 *  $attributes = new Attribute(array('href' => $path));
 *  echo '<a' . $attributes . '>';
 *  // Produces <a href="alert(&quot;xss&quot;);">
 * @endcode
 *
 * The attribute values are considered plain text and are treated as such. If a
 * safe HTML string is detected, it is converted to plain text with
 * PlainTextOutput::renderFromHtml() before being escaped. For example:
 * @code
 *   $value = t('Highlight the @tag tag', ['@tag' => '<em>']);
 *   $attributes = new Attribute(['value' => $value]);
 *   echo '<input' . $attributes . '>';
 *   // Produces <input value="Highlight the &lt;em&gt; tag">
 * @endcode
 *
 * @see \Drupal\Component\Utility\Html::escape()
 * @see \Drupal\Component\Render\PlainTextOutput::renderFromHtml()
 * @see \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols()
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
 *   \Drupal\Component\Attribute\AttributeCollection instead.
 *
 * @see https://www.drupal.org/node/3070485
 */
class Attribute extends AttributeCollection {
}
