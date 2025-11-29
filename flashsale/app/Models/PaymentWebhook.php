<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentWebhook extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'idempotency_key', 'payload'];

    protected $casts = [
        'payload' => 'array', // store JSON payload
    ];

    // Relationship: PaymentWebhook belongs to Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
