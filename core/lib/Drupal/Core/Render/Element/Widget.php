<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Attribute\RenderElement;

/**
 * Provides a widget element.
 *
 * A form wrapper containing basic properties for the widget, attach
 * the widget elements to this wrapper. This element renders to an empty
 * string.
 *
 * @property $field_parents
 *   The 'parents' space for the field in the form. Most widgets can simply
 *   overlook this property. This identifies the location where the field
 *   values are placed within $form_state->getValues(), and is used to
 *   access processing information for the field through the
 *   WidgetBase::getWidgetState() and WidgetBase::setWidgetState() methods.
 * @property $delta
 *   The order of this item in the array of sub-elements. (0, 1, 2, etc.)
 */
#[RenderElement('widget')]
class Widget extends Generic {

}
