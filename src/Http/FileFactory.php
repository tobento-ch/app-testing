<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Testing\Http;

final class FileFactory
{
    /**
     * Create a new fake file.
     *
     * @param string $filename
     * @param null|int $kilobytes
     * @param string $mimeType
     * @return File
     */
    public function createFile(string $filename, null|int $kilobytes = null, string $mimeType = null): File
    {
        $file = new File($filename, tmpfile());

        if ($kilobytes !== null) {
            $file->setSize($kilobytes);
        }

        if ($mimeType) {
            $file->setMimeType($mimeType);
        }

        return $file;
    }

    /**
     * Create a new fake file with given content.
     *
     * @param string $filename
     * @param string $content
     * @param string $mimeType
     * @return File
     */
    public function createFileWithContent(string $filename, string $content, string $mimeType = null): File
    {
        $tmpFile = tmpfile();
        fwrite($tmpFile, $content);

        $file = new File($filename, $tmpFile);

        if ($mimeType) {
            $file->setMimeType($mimeType);
        }

        return $file;
    }

    /**
     * Create a new fake image.
     *
     * @param string $filename
     * @param int $width
     * @param int $height
     * @return File
     */
    public function createImage(string $filename, int $width = 50, int $height = 50): File
    {
        $tmpFile = tmpfile();
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        ob_start();

        $extension = in_array($extension, ['jpeg', 'png', 'gif', 'webp', 'wbmp', 'bmp'])
            ? strtolower($extension)
            : 'jpeg';

        $image = imagecreatetruecolor($width, $height);

        call_user_func("image{$extension}", $image);

        fwrite($tmpFile, ob_get_clean());

        return new File($filename, $tmpFile);
    }
}