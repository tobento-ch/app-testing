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

namespace Tobento\App\Testing\App;

use Tobento\Service\Config\Config as DefaultConfig;
use Tobento\Service\Config\DataInterface;
use Tobento\Service\Config\ConfigLoadException;
use Tobento\Service\Config\ConfigNotFoundException;
use Tobento\Service\Dir\DirInterface;
use Tobento\Service\Collection\Collection;

final class Config extends DefaultConfig
{
    protected array $testData = [];
    
    /**
     * Set test config data.
     *
     * @param array $data
     * @return static $this
     */
    public function setTestData(array $data): static
    {
        $this->testData = $data;
        return $this;
    }
    
    /**
     * Loads a file and stores config is set.
     *
     * @param string $file The file to load.
     * @param null|string $key If a key is set, it stores as such.
     * @param null|int|string $locale
     * @return array The loaded config data.
     * @throws ConfigLoadException
     */        
    public function load(string $file, null|string $key = null, null|int|string $locale = null): array
    {
        $data = parent::load($file, $key, $locale);
        $collection = new Collection($data);
        $file = explode('.', $file)[0];
        $prefix = $file.'.';
        $strlen = strlen($prefix);
        
        foreach($this->testData as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $collection->set(substr($key, $strlen), $value);
            }
        }

        return $collection->all();
    }    
    
    /**
     * Returns the data for the specified file.
     *
     * @param string $file
     * @return DataInterface
     * @throws ConfigLoadException
     */
    public function data(string $file): DataInterface
    {
        $data = parent::data($file);
        $collection = new Collection($data->data());
        $file = explode('.', $file)[0];
        $prefix = $file.'.';
        $strlen = strlen($prefix);
        
        foreach($this->testData as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $collection->set(substr($key, $strlen), $value);
            }
        }
        
        return $data->withData($collection->all());
    }
    
    /**
     * Get a value by key.
     *
     * @param string $key The key.
     * @param mixed $default A default value.
     * @param null|int|string|array $locale 
     *        string: locale,
     *        array: [] if empty gets all languages,
     *        otherwise the keys set ['de', 'en']
     * @return mixed The value or the default value if not exist.
     * throws ConfigNotFoundException
     */
    public function get(string $key, mixed $default = null, null|int|string|array $locale = null): mixed
    {
        if (array_key_exists($key, $this->testData)) {
            return $this->testData[$key];
        }
        
        return parent::get($key, $default, $locale);
    }
    
    /**
     * Returns true if config exists, otherwise false.
     * 
     * @param string $key The key.
     * @param null|string|int|array $locale The locale
     * @return bool
     */
    public function has(string $key, null|int|string|array $locale = null): bool
    {
        if (parent::has($key, $locale)) {
            return true;
        }
        
        return array_key_exists($key, $this->testData);
    }
}