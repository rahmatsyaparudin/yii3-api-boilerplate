<?php

declare(strict_types=1);

namespace App\Domain\Brand;

use App\Domain\Common\Audit\AuditableTrait;
use Yiisoft\Db\ActiveRecord;

final class Brand extends ActiveRecord
{
    use AuditableTrait;

    public static function tableName(): string
    {
        return 'brand';
    }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['status'], 'integer'],
            [['detail_info'], 'safe'],
        ];
    }
}
