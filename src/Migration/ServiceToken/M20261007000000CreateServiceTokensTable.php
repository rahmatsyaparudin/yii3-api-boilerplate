<?php

declare(strict_types=1);

namespace App\Migration\ServiceToken;

// Domain Layer
use App\Shared\Core\Enums\ColumnContract;
// Vendor Layer
use Yiisoft\Db\Constant\IndexType;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Creates service_tokens table in the default database.
 *
 * Long-lived bearer tokens for external services (POS, scripts, etc.)
 * — the SSO-free alternative to JWT. The `token` column stores only the
 * SHA-256 hash of the issued secret; the plaintext is returned once at
 * creation and never persisted. `abilities` holds JSON scopes for later
 * authorization use. Soft-delete via `status` keeps the audit trail.
 */
final class M20261007000000CreateServiceTokensTable implements RevertibleMigrationInterface
{
    private const TABLE_NAME = 'service_tokens';

    public function up(MigrationBuilder $b): void
    {
        $cb = $b->columnBuilder();

        $b->createTable(self::TABLE_NAME, [
            'id'             => $cb::integer()->notNull()->extra(ColumnContract::PG_PRIMARY_KEY_IDENTITY),
            'user_id'        => $cb::integer()->notNull()->comment('Internal user who owns/issued the token'),
            'username'       => $cb::string(100)->notNull()->comment('Service identity — unique across all rows'),
            'name'           => $cb::string(100)->notNull()->comment('External service/app name, e.g. POS Kasir'),
            'token'          => $cb::string(500)->notNull()->comment('SHA-256 hash of the bearer token'),
            'abilities'      => $cb::text()->comment('JSON array of scopes'),
            'whitelist_host' => $cb::json()->comment('Allowed client hosts/IPs; empty = allow all'),
            'last_used_at'   => $cb::datetime()->comment('Last successful authentication'),
            'expires_at'     => $cb::datetime()->comment('Expiry; NULL = never'),
            'status'         => $cb::smallint()->notNull()->defaultValue(ColumnContract::statusDefault('active'))->comment(ColumnContract::statusComment()),
            'detail_info'    => $cb::json()->comment(ColumnContract::detailInfoComment()),
            'created_at'     => $cb::datetime(),
        ]);

        $b->createIndex(self::TABLE_NAME, 'uk_token', ['token'], IndexType::UNIQUE);
        $b->createIndex(self::TABLE_NAME, 'uk_username', ['username'], IndexType::UNIQUE);
        $b->createIndex(self::TABLE_NAME, 'idx_user_id', ['user_id']);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable(self::TABLE_NAME);
    }
}
