<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $model
 * @property string $openid
 * @property string $system 
 * @property string $text 
 * @property string $wx_version
 * @property string $sdk_version
 * @property int $type
 * @property \Carbon\Carbon $last_look_ad_time
 * @property \Carbon\Carbon $created_at
 */
class Bullet extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'bullet';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'created_at' => 'datetime'];
}
