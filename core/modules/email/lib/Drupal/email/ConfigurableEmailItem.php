<?php

/**
 * @file
 * Contains \Drupal\email\ConfigurableEmailItem.
 */

namespace Drupal\email;

use Drupal\Core\Entity\Plugin\field\field_type\EmailItem;
use Drupal\field\FieldInterface;
use Drupal\field\Plugin\Type\FieldType\ConfigFieldItemInterface;

/**
 * Alternative plugin implementation for the 'email' entity field type.
 *
 * Replaces the default implementation and supports configurable fields.
 */
class ConfigurableEmailItem extends EmailItem implements ConfigFieldItemInterface {

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

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state, $has_data) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function instanceSettingsForm(array $form, array &$form_state) {
    return array();
  }


}
