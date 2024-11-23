<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\FuncCall;

class DetailOrder extends Model
{
    use HasFactory;

    protected $table = 'detail_orders';
    protected $primaryKey = 'id_detailorder';
    public $incrementing = false;
    protected $keyType = 'string';

    const CREATED_AT = 'waktu_transaksi';
    const UPDATED_AT = 'updated_on';

    protected $fillable = [
        'id_detailorder',
        'id_order',
        'id_menu',
        'kuantitas',
        'harga_menu',
        'notes',
        'waktu_transaksi',
        'updated_on'
    ];

    protected $dates = ['deleted_at'];

    public function order()
    {
        return $this->belongsTo(Order::class, 'id_order');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'id_menu'); // Perbaiki nama kolom di sini
    }
    
    public function detailAddon()
    {
        return $this->hasMany(DetailAddon::class, 'id_detailorder');
    }
}
