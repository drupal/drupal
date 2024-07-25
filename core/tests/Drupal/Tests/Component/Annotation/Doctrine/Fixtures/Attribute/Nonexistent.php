<?php

namespace Drupal\Tests\Component\Annotation\Doctrine\Fixtures\Attribute;

// @phpstan-ignore attribute.notFound
#[NonexistentAttribute]
final class Nonexistent
{
}
