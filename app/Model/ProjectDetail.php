<?php

declare(strict_types=1);

namespace App\Model;

/**
 * @property int $id 
 * @property int $fdc_id 
 * @property string $url 
 * @property string $building 
 * @property int $type 
 * @property string $units 
 * @property string $floor 
 * @property string $room_num 
 * @property string $status 
 * @property string $last_update 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class ProjectDetail extends AbstractModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'project_detail';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['fdc_id','building','url'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'fdc_id' => 'integer', 'type' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
