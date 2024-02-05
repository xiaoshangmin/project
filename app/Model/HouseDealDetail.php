<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $area 
 * @property int $xml_date_day 
 * @property int $type 
 * @property string $use 
 * @property string $deal_num 
 * @property string $deal_area 
 * @property string $sellable 
 * @property string $sellable_area 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class HouseDealDetail extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'house_deal_detail';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'xml_date_day' => 'integer', 'type' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
