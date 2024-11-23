<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';
    protected $primaryKey = 'id_order';
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    
    const CREATED_AT = 'waktu_transaksi';
    const UPDATED_AT = 'updated_on';

    protected $fillable = [
        'id_order',
        'id_user',
        'antrian',
        'customer',
        'meja',
        'tipe_order',
        'status',
        'total_harga',
        'waktu_transaksi',
        'updated_on'
    ];

    protected $dates = ['deleted_at'];

    public function items()
    {
        return $this->hasMany(DetailOrder::class);
    }
    
    public function detailOrders()
    {
        return $this->hasMany(DetailOrder::class, 'id_order');
    }

}
