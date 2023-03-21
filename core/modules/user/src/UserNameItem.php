<?php

namespace Drupal\user;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;

/**
 * Defines a custom field item class for the 'name' user entity field.
 */
class UserNameItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();

    // Take into account that the name of the anonymous user is an empty string.
    if ($this->getEntity()->isAnonymous()) {
      return $value === NULL;
    }

    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $max_length = min(UserInterface::USERNAME_MAX_LENGTH, $field_definition->getSetting('max_length'));

    // Generate a list of words, which can be used to generate a string.
    $words = explode(' ', $random->sentences(8));

    // Begin with a username that is either 2 or 3 words.
    $count = mt_rand(2, 3);

    // Capitalize the words used in usernames 50% of the time.
    $words = mt_rand(0, 1) ? array_map('ucfirst', $words) : $words;

    // Username is a single long word 50% of the time. In the case of a single
    // long word, sometimes the generated username may also contain periods in
    // the middle of the username.
    $separator = ' ';
    if (mt_rand(0, 1)) {
      $separator = '';
      $count = mt_rand(2, 8);

      // The username will start with a capital letter 50% of the time.
      $words = mt_rand(0, 1) ? array_map('strtolower', $words) : $words;
    }

    $string = implode($separator, array_splice($words, 0, $count));

    // Normalize the string to not be longer than the maximum length, and to not
    // end with a space or a period.
    $values['value'] = rtrim(mb_substr($string, 0, $max_length), ' .');

    return $values;
  }

}
