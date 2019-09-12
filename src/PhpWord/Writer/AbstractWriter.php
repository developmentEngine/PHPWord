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

namespace Devengine\PhpWord\Writer;

use Devengine\PhpWord\Exception\CopyFileException;
use Devengine\PhpWord\Exception\Exception;
use Devengine\PhpWord\PhpWord;
use Devengine\PhpWord\Settings;
use Devengine\PhpWord\Shared\ZipArchive;

/**
 * Abstract writer class
 *
 * @since 0.10.0
 */
abstract class AbstractWriter implements WriterInterface
{
    /**
     * PHPWord object
     *
     * @var \Devengine\PhpWord\PhpWord
     */
    protected $phpWord = null;

    /**
     * Part name and file name pairs
     *
     * @var array
     */
    protected $parts = array();

    /**
     * Individual writers
     *
     * @var array
     */
    protected $writerParts = array();

    /**
     * Paths to store media files
     *
     * @var array
     */
    protected $mediaPaths = array('image' => '', 'object' => '');

    /**
     * Use disk caching
     *
     * @var bool
     */
    private $useDiskCaching = false;

    /**
     * Disk caching directory
     *
     * @var string
     */
    private $diskCachingDirectory = './';

    /**
     * Temporary directory
     *
     * @var string
     */
    private $tempDir = '';

    /**
     * Original file name
     *
     * @var string
     */
    private $originalFilename;

    /**
     * Temporary file name
     *
     * @var string
     */
    private $tempFilename;

    /**
     * Get PhpWord object
     *
     * @throws \Devengine\PhpWord\Exception\Exception
     * @return \Devengine\PhpWord\PhpWord
     */
    public function getPhpWord()
    {
        if (!is_null($this->phpWord)) {
            return $this->phpWord;
        }
        throw new Exception('No PhpWord assigned.');
    }

    /**
     * Set PhpWord object
     *
     * @param \Devengine\PhpWord\PhpWord
     * @return self
     */
    public function setPhpWord(PhpWord $phpWord = null)
    {
        $this->phpWord = $phpWord;

        return $this;
    }

    /**
     * Get writer part
     *
     * @param string $partName Writer part name
     * @return mixed
     */
    public function getWriterPart($partName = '')
    {
        if ($partName != '' && isset($this->writerParts[strtolower($partName)])) {
            return $this->writerParts[strtolower($partName)];
        }

        return null;
    }

    /**
     * Get use disk caching status
     *
     * @return bool
     */
    public function isUseDiskCaching()
    {
        return $this->useDiskCaching;
    }

    /**
     * Set use disk caching status
     *
     * @param bool $value
     * @param string $directory
     *
     * @throws \Devengine\PhpWord\Exception\Exception
     * @return self
     */
    public function setUseDiskCaching($value = false, $directory = null)
    {
        $this->useDiskCaching = $value;

        if (!is_null($directory)) {
            if (is_dir($directory)) {
                $this->diskCachingDirectory = $directory;
            } else {
                throw new Exception("Directory does not exist: $directory");
            }
        }

        return $this;
    }

    /**
     * Get disk caching directory
     *
     * @return string
     */
    public function getDiskCachingDirectory()
    {
        return $this->diskCachingDirectory;
    }

    /**
     * Get temporary directory
     *
     * @return string
     */
    public function getTempDir()
    {
        return $this->tempDir;
    }

    /**
     * Set temporary directory
     *
     * @param string $value
     * @return self
     */
    public function setTempDir($value)
    {
        if (!is_dir($value)) {
            mkdir($value);
        }
        $this->tempDir = $value;

        return $this;
    }

    /**
     * Get temporary file name
     *
     * If $filename is php://output or php://stdout, make it a temporary file
     *
     * @param string $filename
     * @return string
     */
    protected function getTempFile($filename)
    {
        // Temporary directory
        $this->setTempDir(Settings::getTempDir() . uniqid('/PHPWordWriter_', true) . '/');

        // Temporary file
        $this->originalFilename = $filename;
        if (strtolower($filename) == 'php://output' || strtolower($filename) == 'php://stdout') {
            $filename = tempnam(Settings::getTempDir(), 'PhpWord');
            if (false === $filename) {
                $filename = $this->originalFilename; // @codeCoverageIgnore
            } // @codeCoverageIgnore
        }
        $this->tempFilename = $filename;

        return $this->tempFilename;
    }

    /**
     * Cleanup temporary file.
     *
     * @throws \Devengine\PhpWord\Exception\CopyFileException
     */
    protected function cleanupTempFile()
    {
        if ($this->originalFilename != $this->tempFilename) {
            // @codeCoverageIgnoreStart
            // Can't find any test case. Uncomment when found.
            if (false === copy($this->tempFilename, $this->originalFilename)) {
                throw new CopyFileException($this->tempFilename, $this->originalFilename);
            }
            // @codeCoverageIgnoreEnd
            @unlink($this->tempFilename);
        }

        $this->clearTempDir();
    }

    /**
     * Clear temporary directory.
     */
    protected function clearTempDir()
    {
        if (is_dir($this->tempDir)) {
            $this->deleteDir($this->tempDir);
        }
    }

    /**
     * Get ZipArchive object
     *
     * @param string $filename
     *
     * @throws \Exception
     *
     * @return \Devengine\PhpWord\Shared\ZipArchive
     */
    protected function getZipArchive($filename)
    {
        // Remove any existing file
        if (file_exists($filename)) {
            unlink($filename);
        }

        // Try opening the ZIP file
        $zip = new ZipArchive();

        // @codeCoverageIgnoreStart
        // Can't find any test case. Uncomment when found.
        if ($zip->open($filename, ZipArchive::OVERWRITE) !== true) {
            if ($zip->open($filename, ZipArchive::CREATE) !== true) {
                throw new \Exception("Could not open '{$filename}' for writing.");
            }
        }
        // @codeCoverageIgnoreEnd

        return $zip;
    }

    /**
     * Open file for writing
     *
     * @since 0.11.0
     *
     * @param string $filename
     *
     * @throws \Exception
     *
     * @return resource
     */
    protected function openFile($filename)
    {
        $filename = $this->getTempFile($filename);
        $fileHandle = fopen($filename, 'w');
        // @codeCoverageIgnoreStart
        // Can't find any test case. Uncomment when found.
        if ($fileHandle === false) {
            throw new \Exception("Could not open '{$filename}' for writing.");
        }
        // @codeCoverageIgnoreEnd

        return $fileHandle;
    }

    /**
     * Write content to file.
     *
     * @since 0.11.0
     *
     * @param resource $fileHandle
     * @param string $content
     */
    protected function writeFile($fileHandle, $content)
    {
        fwrite($fileHandle, $content);
        fclose($fileHandle);
        $this->cleanupTempFile();
    }

    /**
     * Add files to package.
     *
     * @param \Devengine\PhpWord\Shared\ZipArchive $zip
     * @param mixed $elements
     */
    protected function addFilesToPackage(ZipArchive $zip, $elements)
    {
        foreach ($elements as $element) {
            $type = $element['type']; // image|object|link

            // Skip nonregistered types and set target
            if (!isset($this->mediaPaths[$type])) {
                continue;
            }
            $target = $this->mediaPaths[$type] . $element['target'];

            // Retrive GD image content or get local media
            if (isset($element['isMemImage']) && $element['isMemImage']) {
                $image = call_user_func($element['createFunction'], $element['source']);
                if ($element['imageType'] === 'image/png') {
                    // PNG images need to preserve alpha channel information
                    imagesavealpha($image, true);
                }
                ob_start();
                call_user_func($element['imageFunction'], $image);
                $imageContents = ob_get_contents();
                ob_end_clean();
                $zip->addFromString($target, $imageContents);
                imagedestroy($image);
            } else {
                $this->addFileToPackage($zip, $element['source'], $target);
            }
        }
    }

    /**
     * Add file to package.
     *
     * Get the actual source from an archive image.
     *
     * @param \Devengine\PhpWord\Shared\ZipArchive $zipPackage
     * @param string $source
     * @param string $target
     */
    protected function addFileToPackage($zipPackage, $source, $target)
    {
        $isArchive = strpos($source, 'zip://') !== false;
        $actualSource = null;
        if ($isArchive) {
            $source = substr($source, 6);
            list($zipFilename, $imageFilename) = explode('#', $source);

            $zip = new ZipArchive();
            if ($zip->open($zipFilename) !== false) {
                if ($zip->locateName($imageFilename)) {
                    $zip->extractTo($this->getTempDir(), $imageFilename);
                    $actualSource = $this->getTempDir() . DIRECTORY_SEPARATOR . $imageFilename;
                }
            }
            $zip->close();
        } else {
            $actualSource = $source;
        }

        if (!is_null($actualSource)) {
            $zipPackage->addFile($actualSource, $target);
        }
    }

    /**
     * Delete directory.
     *
     * @param string $dir
     */
    private function deleteDir($dir)
    {
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            } elseif (is_file($dir . '/' . $file)) {
                unlink($dir . '/' . $file);
            } elseif (is_dir($dir . '/' . $file)) {
                $this->deleteDir($dir . '/' . $file);
            }
        }

        rmdir($dir);
    }

    /**
     * Get use disk caching status
     *
     * @deprecated 0.10.0
     *
     * @codeCoverageIgnore
     */
    public function getUseDiskCaching()
    {
        return $this->isUseDiskCaching();
    }
}
