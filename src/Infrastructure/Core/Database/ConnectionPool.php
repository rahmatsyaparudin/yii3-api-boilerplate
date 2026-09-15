<?php

declare(strict_types=1);

namespace App\Infrastructure\Core\Database;

use App\Shared\Core\Exception\ServiceException;
use App\Shared\Core\ValueObject\Message;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Connection as MysqlConnection;
use Yiisoft\Db\Mysql\Driver as MysqlDriver;
use Yiisoft\Db\Mysql\Dsn as MysqlDsn;
use Yiisoft\Db\Pgsql\Connection as PgsqlConnection;
use Yiisoft\Db\Pgsql\Driver as PgsqlDriver;
use Yiisoft\Db\Pgsql\Dsn as PgsqlDsn;
use Yiisoft\Http\Status;

/**
 * Multi-driver connection pool (MySQL/MariaDB and PostgreSQL). Each named
 * connection reads its config from env keys: db.<name>.driver,
 * db.<name>.host, db.<name>.port, db.<name>.name, db.<name>.user,
 * db.<name>.password, db.<name>.charset.
 */
final class ConnectionPool
{
    /** @var array<string, ConnectionInterface> */
    private array $connections = [];

    public function __construct(
        private SchemaCache $schemaCache,
    ) {}

    public function get(string $name): ConnectionInterface
    {
        if (!isset($this->connections[$name])) {
            $envPrefix = 'db.' . $name;

            $driverName = $_ENV[$envPrefix . '.driver'] ?? 'pgsql';
            $host = $_ENV[$envPrefix . '.host'] ?? null;
            $port = $_ENV[$envPrefix . '.port'] ?? null;
            $dbName = $_ENV[$envPrefix . '.name'] ?? null;
            $user = $_ENV[$envPrefix . '.user'] ?? '';
            $pass = $_ENV[$envPrefix . '.password'] ?? '';
            $charset = $_ENV[$envPrefix . '.charset'] ?? null;

            if ($host === null || $dbName === null) {
                throw new ServiceException(
                    translate: Message::create(
                        key: 'service.error',
                        params: ['reason' => "Database connection config not found: {$name}"]
                    ),
                    code: Status::INTERNAL_SERVER_ERROR
                );
            }

            $this->connections[$name] = match ($driverName) {
                'mysql', 'mariadb' => $this->createMysql(
                    $host, (string)($port ?? '3306'), $dbName, $user, $pass, $charset ?? 'utf8mb4'
                ),
                'pgsql' => $this->createPgsql(
                    $host, (string)($port ?? '5432'), $dbName, $user, $pass, $charset
                ),
                default => throw new ServiceException(
                    translate: Message::create(
                        key: 'service.error',
                        params: ['reason' => "Unsupported database driver '{$driverName}' for connection: {$name}"]
                    ),
                    code: Status::INTERNAL_SERVER_ERROR
                ),
            };
        }

        return $this->connections[$name];
    }

    private function createMysql(
        string $host,
        string $port,
        string $dbName,
        string $user,
        string $pass,
        string $charset,
    ): MysqlConnection {
        $driver = new MysqlDriver(new MysqlDsn('mysql', $host, $dbName, $port), $user, $pass);
        $driver->charset($charset);

        return new MysqlConnection(
            driver: $driver,
            schemaCache: $this->schemaCache,
        );
    }

    private function createPgsql(
        string $host,
        string $port,
        string $dbName,
        string $user,
        string $pass,
        ?string $charset,
    ): PgsqlConnection {
        $driver = new PgsqlDriver(new PgsqlDsn('pgsql', $host, $dbName, $port), $user, $pass);
        $driver->charset($charset === 'utf8mb4' ? 'UTF8' : $charset);

        return new PgsqlConnection(
            driver: $driver,
            schemaCache: $this->schemaCache,
        );
    }
}
