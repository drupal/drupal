<?php

/**
 * @file
 * Contains \Drupal\email\Plugin\field\field_type\ConfigurableEmailItem.
 */

namespace Drupal\email\Plugin\field\field_type;

use Drupal\Core\Entity\Plugin\DataType\EmailItem;
use Drupal\field\FieldInterface;

/**
 * Plugin implementation of the 'email' field type.
 *
 * @FieldType(
 *   id = "email",
 *   label = @Translation("E-mail"),
 *   description = @Translation("This field stores an e-mail address in the database."),
 *   default_widget = "email_default",
 *   default_formatter = "email_mailto"
 * )
 */
class ConfigurableEmailItem extends EmailItem {

  /**
   * Defines the max length for an email address
   *
   * The maximum length of an e-mail address is 254 characters. RFC 3696
   * specifies a total length of 320 characters, but mentions that
   * addresses longer than 256 characters are not normally useful. Erratum
   * 1690 was then released which corrected this value to 254 characters.
   * @see http://tools.ietf.org/html/rfc3696#section-3
   * @see http://www.rfc-editor.org/errata_search.php?rfc=3696&eid=1690
   */
  const EMAIL_MAX_LENGTH = 254;

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldInterface $field) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => static::EMAIL_MAX_LENGTH,
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedData()->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    $constraints[] = $constraint_manager->create('ComplexData', array(
      'value' => array(
        'Length' => array(
          'max' => static::EMAIL_MAX_LENGTH,
          'maxMessage' => t('%name: the e-mail address can not be longer than @max characters.', array('%name' => $this->getFieldDefinition()->getFieldLabel(), '@max' => static::EMAIL_MAX_LENGTH)),
        )
      ),
    ));

    return $constraints;
  }

}
