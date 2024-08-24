<?php

declare(strict_types=1);

namespace Drupal\filter_test\Plugin\Filter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\Component\Utility\Xss;

/**
 * Provides a test filter to restrict HTML tags and attributes.
 */
#[Filter(
  id: "filter_test_restrict_tags_and_attributes",
  title: new TranslatableMarkup("Tag and attribute restricting filter"),
  description: new TranslatableMarkup("Used for testing \Drupal\filter\Entity\FilterFormatInterface::getHtmlRestrictions()."),
  type: FilterInterface::TYPE_HTML_RESTRICTOR
)]
class FilterTestRestrictTagsAndAttributes extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $allowed_tags = array_filter($this->settings['restrictions']['allowed'], function ($value) {
      return is_array($value) || (bool) $value !== FALSE;
    });
    return new FilterProcessResult(Xss::filter($text, array_keys($allowed_tags)));
  }

  /**
   * {@inheritdoc}
   */
  public function getHTMLRestrictions() {
    $restrictions = $this->settings['restrictions'];

    // The configuration system stores FALSE as '0' and TRUE as '1'. Fix that.
    if (isset($restrictions['allowed'])) {
      foreach ($restrictions['allowed'] as $tag => $attributes_or_bool) {
        if (!is_array($attributes_or_bool)) {
          $restrictions['allowed'][$tag] = (bool) $attributes_or_bool;
        }
        else {
          foreach ($attributes_or_bool as $attr => $attribute_values_or_bool) {
            if (!is_array($attribute_values_or_bool)) {
              $restrictions['allowed'][$tag][$attr] = (bool) $attribute_values_or_bool;
            }
            else {
              foreach ($attribute_values_or_bool as $attribute_value => $bool) {
                $restrictions['allowed'][$tag][$attr][$attribute_value] = (bool) $bool;
              }
            }
          }
        }
      }
    }

    return $restrictions;
  }

}
