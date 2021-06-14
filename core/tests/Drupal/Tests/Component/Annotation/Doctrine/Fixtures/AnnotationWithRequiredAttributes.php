<?php

namespace Drupal\Tests\Component\Annotation\Doctrine\Fixtures;

/**
 * @Annotation
 * @Target("ALL")
 * @Attributes({
      @Attribute("value",   required = true ,   type = "string"),
      @Attribute("annot",   required = true ,   type = "Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAnnotation"),
   })
 */
final class AnnotationWithRequiredAttributes
{

    public final function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @var string
     */
    private $value;

    /**
     *
     * @var Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAnnotation
     */
    private $annot;

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAnnotation
     */
    public function getAnnot()
    {
        return $this->annot;
    }

}
