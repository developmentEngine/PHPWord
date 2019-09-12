<?php
/**
 * This file is part of PHPWord - A pure PHP library for reading and writing
 * word processing documents.
 *
 * PHPWord is free software distributed under the terms of the GNU Lesser
 * General Public License version 3 as published by the Free Software Foundation.
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code. For the full list of
 * contributors, visit https://github.com/Devengine/PHPWord/contributors.
 *
 * @see         https://github.com/Devengine/PHPWord
 * @copyright   2010-2018 PHPWord contributors
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

namespace Devengine\PhpWord\Writer\HTML\Part;

use Devengine\PhpWord\Exception\Exception;
use Devengine\PhpWord\Writer\AbstractWriter;
use Zend\Escaper\Escaper;

/**
 * @since 0.11.0
 */
abstract class AbstractPart
{
    /**
     * @var \Devengine\PhpWord\Writer\AbstractWriter
     */
    private $parentWriter;

    /**
     * @var \Zend\Escaper\Escaper
     */
    protected $escaper;

    public function __construct()
    {
        $this->escaper = new Escaper();
    }

    /**
     * @return string
     */
    abstract public function write();

    /**
     * @param \Devengine\PhpWord\Writer\AbstractWriter $writer
     */
    public function setParentWriter(AbstractWriter $writer = null)
    {
        $this->parentWriter = $writer;
    }

    /**
     * @throws \Devengine\PhpWord\Exception\Exception
     *
     * @return \Devengine\PhpWord\Writer\AbstractWriter
     */
    public function getParentWriter()
    {
        if ($this->parentWriter !== null) {
            return $this->parentWriter;
        }
        throw new Exception('No parent WriterInterface assigned.');
    }
}
