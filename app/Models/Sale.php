<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'type',
        'service_id',
        'product_id',
        'appointment_id',
        'amount',
        'quantity',
        'total',
        'payment_method',
        'notes',
        'sale_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'total' => 'decimal:2',
        'quantity' => 'integer',
        'sale_date' => 'date',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    // Scopes pour filtrer facilement
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('sale_date', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('sale_date', now()->month)
                     ->whereYear('sale_date', now()->year);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('sale_date', today());
    }
}