<?php

namespace Drupal\telephone\Plugin\migrate\field\d6;

use Drupal\telephone\Plugin\migrate\field\d7\PhoneField as D7PhoneField;

/**
 * Field migration plugin for fields provided by Drupal 6 Phone module.
 *
 * @MigrateField(
 *   id = "d6_phone",
 *   type_map = {
 *     "au_phone" = "telephone",
 *     "be_phone" = "telephone",
 *     "br_phone" = "telephone",
 *     "ca_phone" = "telephone",
 *     "ch_phone" = "telephone",
 *     "cl_phone" = "telephone",
 *     "cl_phone" = "telephone",
 *     "cr_phone" = "telephone",
 *     "cs_phone" = "telephone",
 *     "eg_phone" = "telephone",
 *     "el_phone" = "telephone",
 *     "es_phone" = "telephone",
 *     "fr_phone" = "telephone",
 *     "gb_phone" = "telephone",
 *     "hk_phone" = "telephone",
 *     "hu_phone" = "telephone",
 *     "il_phone" = "telephone",
 *     "int_phone" = "telephone",
 *     "it_phone" = "telephone",
 *     "jo_phone" = "telephone",
 *     "mo_phone" = "telephone",
 *     "nl_phone" = "telephone",
 *     "nz_phone" = "telephone",
 *     "pa_phone" = "telephone",
 *     "pa_phone" = "telephone",
 *     "ph_phone" = "telephone",
 *     "pk_phone" = "telephone",
 *     "pl_phone" = "telephone",
 *     "ru_phone" = "telephone",
 *     "se_phone" = "telephone",
 *     "sg_phone" = "telephone",
 *     "ua_phone" = "telephone",
 *     "za_phone" = "telephone",
 *   },
 *   core = {6},
 *   source_module = "phone",
 *   destination_module = "telephone"
 * )
 */
class PhoneField extends D7PhoneField {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'default' => 'basic_string',
    ];
  }

}
