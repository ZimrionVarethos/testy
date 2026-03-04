//<?php

// namespace App\Models;

// use MongoDB\Laravel\Eloquent\Model;

// class Payment extends Model
// {
//     protected $connection = 'mongodb';
//     protected $collection = 'payments';

//     protected $fillable = [
//         'booking_id',
//         'booking_code',
//         'user_id',
//         'amount',
//         'method',
//         'status',     
//         'midtrans',    
//         'paid_at',
//     ];

//     protected $casts = [
//         'paid_at'    => 'datetime',
//         'amount'     => 'integer',
//     ];
// }