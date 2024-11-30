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

use Nyholm\Psr7\UploadedFile;
use Symfony\Component\Mime\MimeTypes;

/**
 * @psalm-suppress InvalidExtendClass
 */
class File extends UploadedFile
{
    /**
     * The fake file size
     */
    public null|int $fakeSize = null;

    /**
     * The fake file size
     */
    public null|string $fakeMimeType = null;
    
    /**
     * The fake error
     */
    public null|int $fakeError = null;

    /**
     * Create a new file instance
     *
     * @param string $filename
     * @param resource $tempFile
     * @psalm-suppress MethodSignatureMismatch
     * @psalm-suppress MoreSpecificImplementedParamType
     * @psalm-suppress ConstructorSignatureMismatch
     */
    public function __construct(
        private string $filename,
        private $tempFile
    ) {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $mimeType = (new MimeTypes())->getMimeTypes($extension)[0] ?? 'application/octet-stream';

        parent::__construct(
            $this->tempFilePath(),
            fstat($tempFile)['size'],
            UPLOAD_ERR_OK,
            $filename,
            $mimeType
        );
    }

    /**
     * Set the fake size of the file in kilobytes.
     */
    public function setSize(int $kilobytes): static
    {
        $this->fakeSize = $kilobytes * 1024;
        return $this;
    }

    /**
     * Set the fake MIME type for the file.
     */
    public function setMimeType(string $mimeType): static
    {
        $this->fakeMimeType = $mimeType;
        return $this;
    }
    
    /**
     * Set the fake Error for the file.
     */
    public function setError(int $error): static
    {
        $this->fakeError = $error;
        return $this;
    }

    /**
     * Returns the client media type.
     *
     * @psalm-suppress MethodSignatureMismatch
     * @psalm-suppress MissingImmutableAnnotation
     */
    public function getClientMediaType(): null|string
    {
        if ($this->fakeMimeType !== null) {
            return $this->fakeMimeType;
        }

        return parent::getClientMediaType();
    }

    /**
     * Returns the size.
     *
     * @psalm-suppress MethodSignatureMismatch
     * @psalm-suppress MissingImmutableAnnotation
     */
    public function getSize(): int
    {
        if ($this->fakeSize !== null) {
            return $this->fakeSize;
        }

        return parent::getSize();
    }
    
    /**
     * Returns the error.
     *
     * @psalm-suppress MethodSignatureMismatch
     * @psalm-suppress MissingImmutableAnnotation
     */
    public function getError(): int
    {
        if ($this->fakeError !== null) {
            return $this->fakeError;
        }

        return parent::getError();
    }

    /**
     * Returns the tmp file path.
     */
    protected function tempFilePath(): string
    {
        return stream_get_meta_data($this->tempFile)['uri'];
    }
}