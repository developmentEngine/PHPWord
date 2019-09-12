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

namespace Devengine\PhpWord;

use Devengine\PhpWord\Exception\Exception;
use Devengine\PhpWord\Reader\ReaderInterface;
use Devengine\PhpWord\Writer\WriterInterface;

abstract class IOFactory
{
    /**
     * Create new writer
     *
     * @param PhpWord $phpWord
     * @param string $name
     *
     * @throws \Devengine\PhpWord\Exception\Exception
     *
     * @return WriterInterface
     */
    public static function createWriter(PhpWord $phpWord, $name = 'Word2007')
    {
        if ($name !== 'WriterInterface' && !in_array($name, array('ODText', 'RTF', 'Word2007', 'HTML', 'PDF'), true)) {
            throw new Exception("\"{$name}\" is not a valid writer.");
        }

        $fqName = "Devengine\\PhpWord\\Writer\\{$name}";

        return new $fqName($phpWord);
    }

    /**
     * Create new reader
     *
     * @param string $name
     *
     * @throws Exception
     *
     * @return ReaderInterface
     */
    public static function createReader($name = 'Word2007')
    {
        return self::createObject('Reader', $name);
    }

    /**
     * Create new object
     *
     * @param string $type
     * @param string $name
     * @param \Devengine\PhpWord\PhpWord $phpWord
     *
     * @throws \Devengine\PhpWord\Exception\Exception
     *
     * @return \Devengine\PhpWord\Writer\WriterInterface|\Devengine\PhpWord\Reader\ReaderInterface
     */
    private static function createObject($type, $name, $phpWord = null)
    {
        $class = "Devengine\\PhpWord\\{$type}\\{$name}";
        if (class_exists($class) && self::isConcreteClass($class)) {
            return new $class($phpWord);
        }
        throw new Exception("\"{$name}\" is not a valid {$type}.");
    }

    /**
     * Loads PhpWord from file
     *
     * @param string $filename The name of the file
     * @param string $readerName
     * @return \Devengine\PhpWord\PhpWord $phpWord
     */
    public static function load($filename, $readerName = 'Word2007')
    {
        /** @var \Devengine\PhpWord\Reader\ReaderInterface $reader */
        $reader = self::createReader($readerName);

        return $reader->load($filename);
    }

    /**
     * Check if it's a concrete class (not abstract nor interface)
     *
     * @param string $class
     * @return bool
     */
    private static function isConcreteClass($class)
    {
        $reflection = new \ReflectionClass($class);

        return !$reflection->isAbstract() && !$reflection->isInterface();
    }
}
