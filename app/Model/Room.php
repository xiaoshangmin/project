<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $fdc_id 
 * @property int $project_id 
 * @property string $url 
 * @property string $units 
 * @property string $room_num 
 * @property string $floor 
 * @property string $selling_price 
 * @property string $barrier_free 
 * @property string $room_type 
 * @property string $floor_space 
 * @property string $room_space 
 * @property string $share_space 
 * @property string $final_floor_space 
 * @property string $final_room_space 
 * @property string $final_share_space 
 * @property string $status 
 * @property string $last_update 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class Room extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'room';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'fdc_id' => 'integer', 'project_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
