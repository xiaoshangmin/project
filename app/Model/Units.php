<?php

declare(strict_types=1);

namespace App\Model;

/**
 * @property int $id 
 * @property int $fdc_id 
 * @property int $project_id 
 * @property string $url 
 * @property string $units 
 * @property string $last_update 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class Units extends AbstractModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'units';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['fdc_id','project_id','units','url'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'fdc_id' => 'integer', 'project_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
