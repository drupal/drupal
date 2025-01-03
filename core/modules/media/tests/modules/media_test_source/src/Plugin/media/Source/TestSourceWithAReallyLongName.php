<?php

declare(strict_types=1);

namespace Drupal\media_test_source\Plugin\media\Source;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\Attribute\MediaSource;

/**
 * Provides test media source.
 */
#[MediaSource(
    id: 'test_source_with_a_really_long_name',
    label: new TranslatableMarkup('Test source with a really long name'),
    description: new TranslatableMarkup('Test source with a really long name.'),
    allowed_field_types: ['string'],
)]
class TestSourceWithAReallyLongName extends Test {

}
