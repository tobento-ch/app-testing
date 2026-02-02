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

namespace Tobento\App\Testing\Database;

use Tobento\Service\Database\DatabaseInterface;
use Tobento\Service\Database\PdoDatabaseInterface;
use Tobento\Service\Database\PdoDatabase;
use Tobento\Service\Database\Storage\StorageDatabaseInterface;
use Tobento\Service\Storage;
use Tobento\Service\Filesystem\Dir;
use PDO;

class Cleaner
{
    /**
     * @var array<array-key, PDO>
     */
    protected array $pdos = [];
    
    /**
     * Truncate database.
     *
     * @param DatabaseInterface $database
     * @return static $this
     */
    public function truncateDatabase(DatabaseInterface $database): static
    {
        if ($database instanceof PdoDatabaseInterface) {
            return $this->truncatePdoDatabase($database);
        }
        
        if ($database instanceof StorageDatabaseInterface) {
            $storage = $database->storage();
            
            if (
                $storage instanceof Storage\PdoMariaDbStorage
                || $storage instanceof Storage\PdoMySqlStorage
                || $storage instanceof Storage\PdoSqliteStorage
            ) {
                return $this->truncatePdoDatabase(new PdoDatabase(pdo: $storage->pdo(), name: ''));
            }
            
            if ($storage instanceof Storage\JsonFileStorage) {
                (new Dir())->delete($storage->dir());
                return $this;
            }
            
            return $this;
        }
        
        return $this;
    }
    
    /**
     * Truncate database.
     *
     * @param PdoDatabaseInterface $database
     * @return static $this
     */
    protected function truncatePdoDatabase(PdoDatabaseInterface $database): static
    {
        foreach ($this->pdos as $pdo) {
            if ($pdo === $database->pdo()) {
                return $this;
            }
        }

        $pdo = $database->pdo();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            // Get all tables except internal sqlite_ tables
            $tables = $database->execute(
                statement: "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"
            )->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $database->execute(statement: 'DELETE FROM "'.$table.'"');
            }

            // Optional: clean up DB file
            //$database->execute(statement: 'VACUUM');
        } else {
            // MySQL / MariaDB
            $tables = $database->execute(
                statement: 'SHOW TABLES'
            )->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $database->execute(
                    statement: 'TRUNCATE `'.$table.'`'
                );
            }
        }

        $this->pdos[] = $pdo;

        return $this;
    }
}