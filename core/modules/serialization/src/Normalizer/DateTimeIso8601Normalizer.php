<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\TypedData\Plugin\DataType\DateTimeIso8601;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * Converts values for the DateTimeIso8601 data type to RFC3339.
 *
 * @internal
 */
class DateTimeIso8601Normalizer extends DateTimeNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $allowedFormats = [
    'RFC 3339' => \DateTime::RFC3339,
    'ISO 8601' => \DateTime::ISO8601,
    // @todo Remove this in https://www.drupal.org/project/drupal/issues/2958416.
    // RFC3339 only covers combined date and time representations. For date-only
    // representations, we need to use ISO 8601. There isn't a constant on the
    // \DateTime class that we can use, so we have to hardcode the format.
    // @see https://en.wikipedia.org/wiki/ISO_8601#Calendar_dates
    // @see \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATE_STORAGE_FORMAT
    'date-only' => 'Y-m-d',
  ];

  /**
   * {@inheritdoc}
   */
  public function normalize($datetime, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    assert($datetime instanceof DateTimeIso8601);
    $field_item = $datetime->getParent();
    // @todo Remove this in https://www.drupal.org/project/drupal/issues/2958416.
    if ($field_item instanceof DateTimeItem && $field_item->getFieldDefinition()->getFieldStorageDefinition()->getSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
      $drupal_date_time = $datetime->getDateTime();
      if ($drupal_date_time === NULL) {
        return $drupal_date_time;
      }
      return $drupal_date_time->format($this->allowedFormats['date-only']);
    }
    return parent::normalize($datetime, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []): mixed {
    // @todo Move the date-only handling out of here in https://www.drupal.org/project/drupal/issues/2958416.
    if (isset($context['target_instance'])) {
      $field_definition = $context['target_instance']->getFieldDefinition();
    }
    elseif (isset($context['field_definition'])) {
      $field_definition = $context['field_definition'];
    }
    else {
      throw new InvalidArgumentException('$context[\'target_instance\'] or $context[\'field_definition\'] must be set to denormalize with the DateTimeIso8601Normalizer');
    }

    $datetime_type = $field_definition->getSetting('datetime_type');
    $is_date_only = $datetime_type === DateTimeItem::DATETIME_TYPE_DATE;

    if ($is_date_only) {
      $context['datetime_allowed_formats'] = array_intersect_key($this->allowedFormats, ['date-only' => TRUE]);
      $datetime = parent::denormalize($data, $class, $format, $context);
      if (!$datetime instanceof \DateTime) {
        return $datetime;
      }
      return $datetime->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
    }

    $context['datetime_allowed_formats'] = array_diff_key($this->allowedFormats, ['date-only' => TRUE]);
    $datetime = parent::denormalize($data, $class, $format, $context);
    if (!$datetime instanceof \DateTime) {
      return $datetime;
    }
    $datetime->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
    return $datetime->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      DateTimeIso8601::class => TRUE,
    ];
  }

}
