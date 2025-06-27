<?php

namespace Drupal\Core\Render\Element;

/**
 * Defines how and where a title should be displayed for a form element.
 */
enum TitleDisplay: string {

  // Label goes before the element (default for most elements).
  case Before = 'before';

  // Label goes after the element (default for radio elements).
  case After = 'after';

  // Label is present in the markup but made invisible using CSS.
  case Invisible = 'invisible';

  // Label is set as the title attribute, displayed as a tooltip on hover.
  case Attribute = 'attribute';

}
