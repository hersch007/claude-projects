<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'customer_id', 'product_id', 'amount', 'transaction_id',
        'status', 'notes', 'payment_date', 'renewal_date', 'payment_method', 'gateway_response',
        'cardholder_name', 'card_last4', 'card_brand', 'needs_review',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'payment_date'     => 'datetime',
        'renewal_date'     => 'date',
        'gateway_response' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function markAsPaid(string $transactionId = null): bool
    {
        return $this->update([
            'status'         => 'paid',
            'transaction_id' => $transactionId ?? 'MANUAL-' . strtoupper(uniqid()),
            'payment_date'   => now(),
        ]);
    }

    public function scopeNeedsReview($query)
    {
        return $query->where('needs_review', true);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
