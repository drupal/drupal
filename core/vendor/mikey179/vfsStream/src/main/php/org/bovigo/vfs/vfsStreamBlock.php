<?php
/**
 * This file is part of vfsStream.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  org\bovigo\vfs
 */
namespace org\bovigo\vfs;

/**
 * Block container.
 *
 * @api
 */
class vfsStreamBlock extends vfsStreamFile
{
    /**
     * constructor
     *
     * @param  string  $name
     * @param  int     $permissions  optional
     */
    public function __construct($name, $permissions = null)
    {
        if (empty($name)) {
            throw new vfsStreamException('Name of Block device was empty');
        }
        parent::__construct($name, $permissions);

        $this->type = vfsStreamContent::TYPE_BLOCK;
    }
}
