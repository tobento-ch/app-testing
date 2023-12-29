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

namespace Tobento\App\Testing\FileStorage;

use Tobento\Service\FileStorage\Flysystem\Storage;
use PHPUnit\Framework\TestCase;

final class TestStorage extends Storage
{
    private array $deleted = [];
    private array $created = [];
    private array $copied = [];
    private array $moved = [];
    private array $visibility = [];
    private array $createdFolders = [];
    private array $deletedFolders = [];
    
    private function filterFiles(array $files, \Closure $callback): array
    {
        return array_filter($files, static function (array $data) use ($callback): bool {
            return $callback($data);
        });
    }
    
    public function assertExists(string $path): static
    {
        TestCase::assertTrue(
            $this->exists(path: $path),
            sprintf('The expected [%s] file does not exist.', $path)
        );
        
        return $this;
    }
    
    public function assertNotExist(string $path): static
    {
        TestCase::assertFalse(
            $this->exists(path: $path),
            sprintf('The unexpected [%s] file does exist.', $path)
        );
        
        return $this;
    }
    
    public function assertCreated(string $path): static
    {
        $files = $this->filterFiles($this->created, function (array $data) use ($path) {
            return $data['path'] === $path;
        });

        TestCase::assertTrue(
            count($files) > 0,
            sprintf('The expected [%s] file was not created.', $path)
        );
        
        return $this;
    }
    
    public function assertNotCreated(string $path): static
    {
        $files = $this->filterFiles($this->created, function (array $data) use ($path) {
            return $data['path'] === $path;
        });

        TestCase::assertTrue(
            count($files) === 0,
            sprintf('The expected [%s] file was created.', $path)
        );
        
        return $this;
    }

    public function write(string $path, mixed $content, null|string $visibility = null): void
    {
        parent::write($path, $content, $visibility);
        
        $this->created[] = compact('path', 'visibility');
    }
    
    public function assertVisibilityChanged(string $path): static
    {
        $files = $this->filterFiles($this->visibility, function (array $data) use ($path) {
            return $data['path'] === $path;
        });

        TestCase::assertTrue(
            count($files) > 0,
            sprintf('The expected [%s] file visibility was not changed.', $path)
        );
        
        return $this;
    }

    public function assertVisibilityNotChanged(string $path): static
    {
        $files = $this->filterFiles($this->visibility, function (array $data) use ($path) {
            return $data['path'] === $path;
        });

        TestCase::assertTrue(
            count($files) === 0,
            sprintf('The expected [%s] file visibility was changed.', $path)
        );
        
        return $this;
    }
    
    public function setVisibility(string $path, string $visibility): void
    {
        parent::setVisibility($path, $visibility);
        
        $this->visibility[] = compact('path', 'visibility');
    }
    
    public function assertCopied(string $from, string $to): static
    {
        $files = $this->filterFiles($this->copied, function (array $data) use ($from, $to) {
            return $data['from'] === $from && $data['to'] === $to;
        });

        TestCase::assertTrue(
            count($files) > 0,
            sprintf('The expected [%s] file was not copied.', $from)
        );
        
        return $this;
    }

    public function assertNotCopied(string $from, string $to): static
    {
        $files = $this->filterFiles($this->copied, function (array $data) use ($from, $to) {
            return $data['from'] === $from && $data['to'] === $to;
        });

        TestCase::assertTrue(
            count($files) === 0,
            sprintf('The expected [%s] file was copied.', $from)
        );
        
        return $this;
    }
    
    public function copy(string $from, string $to): void
    {
        parent::copy($from, $to);

        $this->copied[] = compact('from', 'to');
    }

    public function assertMoved(string $from, string $to): static
    {
        $files = $this->filterFiles($this->moved, function (array $data) use ($from, $to) {
            return $data['from'] === $from && $data['to'] === $to;
        });

        TestCase::assertTrue(
            count($files) > 0,
            sprintf('The expected [%s] file was not moved.', $from)
        );
        
        return $this;
    }

    public function assertNotMoved(string $from, string $to): static
    {
        $files = $this->filterFiles($this->moved, function (array $data) use ($from, $to) {
            return $data['from'] === $from && $data['to'] === $to;
        });

        TestCase::assertTrue(
            count($files) === 0,
            sprintf('The expected [%s] file was moved.', $from)
        );
        
        return $this;
    }
    
    public function move(string $from, string $to): void
    {
        parent::move($from, $to);

        $this->moved[] = compact('from', 'to');
    }
    
    public function assertDeleted(string $path): static
    {
        $files = $this->filterFiles($this->deleted, function (array $data) use ($path) {
            return $data['path'] === $path;
        });

        TestCase::assertTrue(
            count($files) > 0,
            sprintf('The expected [%s] file was not deleted.', $path)
        );
        
        return $this;
    }

    public function assertNotDeleted(string $path): static
    {
        $files = $this->filterFiles($this->deleted, function (array $data) use ($path) {
            return $data['path'] === $path;
        });

        TestCase::assertTrue(
            count($files) === 0,
            sprintf('The expected [%s] file was deleted.', $path)
        );
        
        return $this;
    }
    
    public function delete(string $path): void
    {
        parent::delete($path);

        $this->deleted[] = compact('path');
    }
    
    public function assertFolderExists(string $path): static
    {
        TestCase::assertTrue(
            $this->folderExists(path: $path),
            sprintf('The expected [%s] folder does not exist.', $path)
        );
        
        return $this;
    }
    
    public function assertFolderNotExist(string $path): static
    {
        TestCase::assertFalse(
            $this->folderExists(path: $path),
            sprintf('The unexpected [%s] folder does exist.', $path)
        );
        
        return $this;
    }
    
    public function assertFolderCreated(string $path): static
    {
        $files = $this->filterFiles($this->createdFolders, function (array $data) use ($path) {
            return $data['path'] === $path;
        });

        TestCase::assertTrue(
            count($files) > 0,
            sprintf('The expected [%s] folder was not created.', $path)
        );
        
        return $this;
    }
    
    public function assertFolderNotCreated(string $path): static
    {
        $files = $this->filterFiles($this->createdFolders, function (array $data) use ($path) {
            return $data['path'] === $path;
        });

        TestCase::assertTrue(
            count($files) === 0,
            sprintf('The expected [%s] folder was created.', $path)
        );
        
        return $this;
    }
    
    public function createFolder(string $path): void
    {
        parent::createFolder($path);

        $this->createdFolders[] = compact('path');
    }
    
    public function assertFolderDeleted(string $path): static
    {
        $files = $this->filterFiles($this->deletedFolders, function (array $data) use ($path) {
            return $data['path'] === $path;
        });

        TestCase::assertTrue(
            count($files) > 0,
            sprintf('The expected [%s] folder was not deleted.', $path)
        );
        
        return $this;
    }

    public function assertFolderNotDeleted(string $path): static
    {
        $files = $this->filterFiles($this->deletedFolders, function (array $data) use ($path) {
            return $data['path'] === $path;
        });

        TestCase::assertTrue(
            count($files) === 0,
            sprintf('The expected [%s] folder was deleted.', $path)
        );
        
        return $this;
    }
    
    public function deleteFolder(string $path): void
    {
        parent::deleteFolder($path);

        $this->deletedFolders[] = compact('path');
    }
}