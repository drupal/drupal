<?php

namespace Drupal\text\Plugin\migrate\field\d7;

use Drupal\text\Plugin\migrate\field\d6\TextField as D6TextField;

/**
 * @MigrateField(
 *   id = "d7_text",
 *   type_map = {
 *     "text" = "text",
 *     "text_long" = "text_long",
 *     "text_with_summary" = "text_with_summary"
 *   },
 *   core = {7}
 * )
 */
class TextField extends D6TextField {}
