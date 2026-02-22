<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Apartment extends Model
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_IN_PLEDGE = 'in_pledge';
    public const STATUS_SOLD = 'sold';

    protected $fillable = [
        'project_id',
        'apartment_number',
        'entrance',
        'floor',
        'living_area',
        'rooms_count',
        'layout_photo_path',
        'status',
        'owner_data',
        'ddu_contract_number',
        'price',
        'client_id',
    ];

    protected $casts = [
        'living_area' => 'decimal:2',
        'price' => 'decimal:2',
        'floor' => 'integer',
        'rooms_count' => 'integer',
    ];

    public static function statusLabels(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Свободна',
            self::STATUS_IN_PLEDGE => 'В залоге',
            self::STATUS_SOLD => 'Продана',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** Стоимость м² = цена / площадь (вычисляемое поле) */
    public function getPricePerSqmAttribute(): ?float
    {
        $price = (float) $this->price;
        $area = (float) $this->living_area;
        if ($price > 0 && $area > 0) {
            return round($price / $area, 2);
        }
        return null;
    }

    public function getLayoutPhotoUrlAttribute(): ?string
    {
        if (!$this->layout_photo_path) {
            return null;
        }
        return Storage::disk('public')->exists($this->layout_photo_path)
            ? asset('storage/' . $this->layout_photo_path)
            : null;
    }
}
