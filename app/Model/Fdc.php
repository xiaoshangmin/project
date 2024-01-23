<?php

declare(strict_types=1);

namespace App\Model;

/**
 * @property int $id 
 * @property string $pre_sale_cert_name 
 * @property string $project_name 
 * @property string $last_update 
 */
class Fdc extends AbstractModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'fdc';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id','pre_sale_cert_name','project_name','ent','area','approve_time'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer'];

    public bool $timestamps = false;
}
