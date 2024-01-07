<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $pre_sale_cert_name 
 * @property string $project_name 
 * @property string $last_update 
 */
class Fdc extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'fdc';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer'];

    public bool $timestamps = false;
}
