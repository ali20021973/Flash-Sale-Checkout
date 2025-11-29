<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['hold_id', 'status'];

    // Relationship: Order belongs to Hold
    public function hold()
    {
        return $this->belongsTo(Hold::class);
    }

    // Relationship: Order has many PaymentWebhooks
    public function webhooks()
    {
        return $this->hasMany(PaymentWebhook::class);
    }
}
