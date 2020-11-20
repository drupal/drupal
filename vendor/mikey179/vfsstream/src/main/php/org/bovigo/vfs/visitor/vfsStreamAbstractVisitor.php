<?php
/**
 * This file is part of vfsStream.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  org\bovigo\vfs
 */
namespace org\bovigo\vfs\visitor;
use org\bovigo\vfs\vfsStreamContent;
use org\bovigo\vfs\vfsStreamBlock;

/**
 * Abstract base class providing an implementation for the visit() method.
 *
 * @since  0.10.0
 * @see    https://github.com/mikey179/vfsStream/issues/10
 */
abstract class vfsStreamAbstractVisitor implements vfsStreamVisitor
{
    /**
     * visit a content and process it
     *
     * @param   vfsStreamContent  $content
     * @return  vfsStreamVisitor
     * @throws  \InvalidArgumentException
     */
    public function visit(vfsStreamContent $content)
    {
        switch ($content->getType()) {
            case vfsStreamContent::TYPE_BLOCK:
                $this->visitBlockDevice($content);
                break;

            case vfsStreamContent::TYPE_FILE:
                $this->visitFile($content);
                break;

            case vfsStreamContent::TYPE_DIR:
                if (!$content->isDot()) {
                    $this->visitDirectory($content);
                }

                break;

            default:
                throw new \InvalidArgumentException('Unknown content type ' . $content->getType() . ' for ' . $content->getName());
        }

        return $this;
    }

    /**
     * visit a block device and process it
     *
     * @param   vfsStreamBlock $block
     * @return  vfsStreamVisitor
     */
    public function visitBlockDevice(vfsStreamBlock $block)
    {
        return $this->visitFile($block);
    }
}
