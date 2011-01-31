<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * @file
 * Theme function overrides
 *
 * @category  LiteCommerce themes
 * @package   LiteCommerce3 theme
 * @author    Creative Development LLC <info@cdev.ru>
 * @copyright Copyright (c) 2010 Creative Development LLC <info@cdev.ru>. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://www.litecommerce.com/
 * @see       ____file_see____
 * @since	  1.0.0
 */

/**
 * A theme-wrapper function for the main menu
 * 
 * @param array $vars Variables
 * @return void
 * @access public
 */
function lc3_clean_menu_tree__main_menu($vars)
{
	$attributes = isset($vars['tree']['#attributes']) ? $vars['tree']['#attributes'] : array('class' => 'menu menu-tree');
	return '<ul '.drupal_attributes($attributes).'>'.$vars['tree']['#children'].'</ul>';
}

/**
 * Theme function rendering the breadcrumbs
 * 
 * @param array $vars Variables 
 * @return void
 * @access public
 */
function lc3_clean_breadcrumb($vars)
{
	$breadcrumb = $vars['breadcrumb'];

    if (!empty($breadcrumb)) {

        // Replace the home link with an image
        $img = '<img src="' . base_path() . path_to_theme() . '/images/home.png" alt="'.t('Home page').'" />';
        $breadcrumb[0] = preg_replace(
            '/^(<a href=[^>]+)>([^<]*)(<\/a>)$/',
            '\1 class="home-link">' . $img . '\3',
            $breadcrumb[0]
        );

        // Wrap text breadcrumb items into a tag in order to align them vertically
        foreach ($breadcrumb as $k => $v) {
            if (!empty($v) && '<' != substr($v, 0, 1)) {
                $breadcrumb[$k] = '<span>' . $v . '</span>';
            }
        }

        // Hide breadcrumb if breadcrumbs list has only first element
        if (1 < count($breadcrumb)) {

            $separator = '<span class="separator">' . chr(194) . chr(187) . '</span>';

        	// Provide a navigational heading to give context for breadcrumb links to
    	    // screen-reader users. Make the heading invisible with .element-invisible.
            $html = '<h2 class="element-invisible">' . t('You are here') . '</h2>'
				. '<div class="breadcrumb">' . implode(' ' . $separator . ' ', $breadcrumb) . '</div>';

        	return $html;
        }
    }

}

/**
 * Theme function rendering taxonomy terms
 * 
 * @param array $vars Variables 
 * @return void
 * @access public
 */
function lc3_clean_field__taxonomy_term_reference($vars)
{
	$output = '';

	// Render the label, if it's not hidden.
	if (!$vars['label_hidden']) {
		$output .= '<span class="field-label"' . $vars['title_attributes'] . '>' . $vars['label'] . ': </span>';
	}

	// Render the items.
	$output .= '<ul class="inline field-items"' . $vars['content_attributes'] . '>';
	$n = count($vars['items']);
	foreach ($vars['items'] as $delta => $item) {
		$classes = 'field-item ' . ($delta % 2 ? 'odd' : 'even');
		$comma = (--$n) ? '<span class="comma">,</span>' : '';
		$output .= '<li class="' . $classes . '"' . $vars['item_attributes'][$delta] . '>' . drupal_render($item) . $comma . '</li>';
	}
	$output .= '</ul>';

	// Render the top-level DIV.
	$output = '<div class="terms ' . $vars['classes'] . '"' . $vars['attributes'] . '>' . $output . '</div>';

	return $output;

}

/**
 * Renders an element that can be shown as a popup box
 *
 * @param array $vars Variables
 *
 * @return HTML code of the element
 * @see    ____func_see____
 * @since  1.0.0
 */
function lc3_clean_popup_box($vars)
{
    global $theme_path;

    $html = '<div id="' . $vars['popup']['#id'] . '" style="display: none;">';

    $html .= '<a href="#" class="close-link" onclick="javascript: lc3_clean_close_popup(); return false;">'
        . theme_image(array('path' => $theme_path . '/images/spacer.gif', 'alt' => t('Close'), 'attributes' => array()))
        . '</a>';
    $html .= '<div class="block-container"><div class="block-subcontainer">';

    if ($vars['popup']['#subject']) {
        $html .= '<h1>' . check_plain($vars['popup']['#subject']) . '</h1>';
    }

    if ($vars['popup']['#children']) {
        $html .= '<div class="content">' . $vars['popup']['#children'] . '</div>';
    }

    $html .= '</div></div></div>';

    return $html;
}

/**
 * Returns HTML for a query pager.
 *
 * Menu callbacks that display paged query results should call theme('pager') to
 * retrieve a pager control so that users can view other results. Format a list
 * of nearby pages with additional query results.
 *
 * @param array $vars An array containing an associative array with #attributes, #button_type, #name and #value properties
 *
 * @return string
 */
function lc3_clean_button($vars)
{
    $element = $vars['element'];
    $element['#attributes']['type'] = 'submit';
    element_set_attributes($element, array('id', 'name', 'value'));

    $element['#attributes']['class'][] = 'form-' . $element['#button_type'];
    if (!empty($element['#attributes']['disabled'])) {
        $element['#attributes']['class'][] = 'form-button-disabled';
    }

    return '<button' . drupal_attributes($element['#attributes']) . '><span>' . check_plain($element['#value']) . "</span></button>\n";

}

/**
 * Renders a pager
 *
 * @param array $vars Array containing:
 *   - tags: an array of labels for the controls in the pager;
 *   - element: an optional integer to distinguish between multiple pagers on one page;
 *   - parameters: An associative array of query string parameters to append to the pager links.
 *   - quantity: The number of pages in the list.
 *
 * @return string
 */
function lc3_clean_pager($vars)
{
    global $pager_page_array, $pager_total;

    $tags = $vars['tags'];
    $element = $vars['element'];
    $parameters = $vars['parameters'];
    // The maximum number of items in the pager cannot be less than 5 otherwise user won't be able to access some pages
    $quantity = max(5, $vars['quantity']);

    // Calculate various markers within this pager piece:
    // Middle is used to "center" pages around the current page.
    $pager_middle = ceil($quantity / 2);
    // current is the page we are currently paged to
    $pager_current = $pager_page_array[$element] + 1;
    // max is the maximum page number
    $pager_max = $pager_total[$element];
    // first is the first page listed by this pager piece (re quantity)
    $pager_first = $pager_current - $pager_middle + 1;
    // last is the last page listed by this pager piece (re quantity)
    $pager_last = $pager_current + $quantity - $pager_middle;
   // End of marker calculations.

    // Prepare for generation loop.
    if ($pager_last > $pager_max) {
        // Adjust "center" if at end of query.
        $pager_first = max(1, $pager_first - $pager_last + $pager_max);
        $pager_last = $pager_max;
    }
    if ($pager_first <= 0) {
        // Adjust "center" if at start of query.
        $pager_last = min($pager_max, $pager_last + (1 - $pager_first));
        $pager_first = 1;
    }

    switch ($pager_first) {
        case 1:
            $from = 2;
            break;
        case 2:
            $from = ($quantity >= $pager_max) ? 2 : 3;
            break;
        default:
            $from = $pager_first;
    }

    switch ($pager_last) {
        case ($pager_max):
            $till = $pager_max - 1;
            break;
        case ($pager_max - 1):
            $till = ($quantity >= $pager_max) ? ($pager_max - 1) : ($pager_max - 2);
            break;
        default:
            $till = $pager_last;
    }

    $li_first = theme('pager_first', array('text' => (isset($tags[0]) ? $tags[0] : 1), 'element' => $element, 'parameters' => $parameters));
    $li_previous = theme('pager_previous', array('text' => (isset($tags[1]) ? $tags[1] : t('Previous')), 'element' => $element, 'interval' => 1, 'parameters' => $parameters));
    $li_next = theme('pager_next', array('text' => (isset($tags[3]) ? $tags[3] : t('Next')), 'element' => $element, 'interval' => 1, 'parameters' => $parameters));
    $li_last = theme('pager_last', array('text' => (isset($tags[4]) ? $tags[4] : $pager_max), 'element' => $element, 'parameters' => $parameters));

    if ($pager_total[$element] > 1) {

        if ($li_previous && ($pager_current > 1)) {
            $items[] = array(
                'class' => array('item', 'previous-page'),
                'data' => $li_previous,
             );
        }

        if ($li_first) {
            $items[] = array(
               'class' => array('item', 'first-page', 'page-0'),
               'data' => $li_first,
            );
        } else {
            $items[] = array(
                'class' => array('item', 'first-page', 'page-0', 'selected'),
                'data' => "<span>1</span>",
            );
        }

        if ($from > 2) {
            $items[] = array(
                'class' => array('more-pages'),
                'data' => '<span>...</span>',
            );
        }
        
        for ($i = $from; $i <= $till; $i++) {
            if ($i < $pager_current) {
                $items[] = array(
                    'class' => array('item', 'page-'.($i-1)),
                    'data' => theme('pager_previous', array('text' => $i, 'element' => $element, 'interval' => ($pager_current - $i), 'parameters' => $parameters)),
                );
            }
            if ($i == $pager_current) {
                $items[] = array(
                    'class' => array('item', 'selected', 'page-'.($i-1)),
                    'data' => "<span>$i</span>",
                );
            }
            if ($i > $pager_current) {
                $items[] = array(
                    'class' => array('item', 'page-'.($i-1)),
                    'data' => theme('pager_next', array('text' => $i, 'element' => $element, 'interval' => ($i - $pager_current), 'parameters' => $parameters)),
                );
            }
        }

        if ($till < ($pager_max - 1)) {
            $items[] = array(
                'class' => array('more-pages'),
                'data' => '<span>...</span>',
            );
        }

        if ($li_last) {
            $items[] = array(
                'class' => array('item', 'last-page', 'page-'.($pager_max-1)),
                'data' => $li_last,
            );
        } else {
            $items[] = array(
                'class' => array('item', 'last-page', 'page-'.($pager_max-1), 'selected'),
                'data' => "<span>$pager_max</span>",
            );
        }

        if ($li_next && ($pager_current < $pager_max)) {
            $items[] = array(
                'class' => array('item', 'next-page'),
                'data' => $li_next,
            );
        }

        return '<h2 class="element-invisible">' . t('Pages') . '</h2>' . theme('item_list', array(
            'items' => $items,
            'attributes' => array('class' => array('pager')),
        ));
    }

}

/**
 * @see theme_status_messages(), lccms_theme()
 * http://api.drupal.org/api/function/theme_status_messages/6
 *
 * Added: A wrapper <div> with to class attributes: table-wrapper, clearfix to
 * deal with overflow and word-wrap and word-break issues.
 */
function lc3_clean_status_messages($vars)
{
    global $theme_path;

    $prefixes = array(
        'error' => t('Error: '),
        'warnin' => t('Warning: '),
    );

    $list = drupal_get_messages($vars['display']);

    $output = '<div id="status-messages"' . ($list ? '' : ' style="display: none;"') . '>' . "\n"
        . '<a href="' . request_uri() . '" class="close" title="' . t('Close') . '">'
        . theme_image(array('path' => $theme_path . '/images/spacer.gif', 'alt' => t('Close'), 'attributes' => array()))
        . '</a>' . "\n"
        . '<ul>' . "\n";

    if ($list) {
        foreach ($list as $type => $messages) {

            $prefix = isset($prefixes[$type]) ? '<em>'.$prefixes[$type].'</em>' : '';

            foreach ($messages as $message) {
                $output .= '<li class="' . $type . '">' . $prefix . $message . '</li>' . "\n";
            }
        }

    } else {
        $output .= '<li class="dump">Dump</li>';
    }

    $output .= '</ul>' . "\n"
        . '</div>' . "\n";

    if ($list) {
        $output .= '<script type="text/javascript">' . "\n"
        . '<!--' . "\n"
        . 'var obj = document.getElementById(\'messages\');'
        . 'if (obj) { obj.style.display = \'none\'; }' . "\n"
        . '-->' . "\n"
        . '</script>' . "\n";
    }

    return $output;
}

/**
 * Returns HTML for a sort icon.
 *
 * @param array $vars Array containing an element (with 'style' key) set to either 'asc' or 'desc'
 * @return string
 */
function lc3_clean_tablesort_indicator($vars)
{
    if ($vars['style'] == 'asc') {
        $title = t('sort ascending');
        $char = '&darr;';
        $class = 'ascending-order';
    } else {
        $title = t('sort descending');
        $char = '&uarr;';
        $class = 'descending-order';
    }

    return "<span class=\"tablesort-indicator $class\" title=\"$title\"> <span>$char</span></span>";
}

