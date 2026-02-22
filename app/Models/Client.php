<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Hash;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'birth_date',
        'email',
        'phone',
        'telegram_id',
        'telegram_username',
        'cabinet_password',
        'registered_at',
        'balance',
        'status',
        'photo_path',
        'calendar_feed_token',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'registered_at' => 'date',
        'balance' => 'decimal:2',
    ];

    protected $hidden = [
        'cabinet_password',
    ];

    public function verifyCabinetPassword(string $plain): bool
    {
        if ($this->cabinet_password) {
            return Hash::check($plain, $this->cabinet_password);
        }
        // Стартовый пароль — дата рождения (ДД.ММ.ГГГГ или ГГГГ-ММ-ДД)
        if (!$this->birth_date) {
            return false;
        }
        $normalized = preg_replace('/\s+/', '', $plain);
        $birthFormatted = $this->birth_date->format('d.m.Y');
        $birthIso = $this->birth_date->format('Y-m-d');
        return $normalized === $birthFormatted || $normalized === $birthIso;
    }

    /** Формат даты рождения для подсказки стартового пароля */
    public function getBirthDateForPasswordHint(): ?string
    {
        return $this->birth_date?->format('d.m.Y');
    }

    public function balanceTransactions(): HasMany
    {
        return $this->hasMany(BalanceTransaction::class)->orderByDesc('created_at');
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function customValues(): HasMany
    {
        return $this->hasMany(ClientCustomValue::class);
    }

    public function customFields(): BelongsToMany
    {
        return $this->belongsToMany(CustomField::class, 'client_custom_values')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getCustomFieldValue(string $fieldName): ?string
    {
        $cv = $this->customValues()->whereHas('customField', function ($q) use ($fieldName) { return $q->where('name', $fieldName); })->first();
        return $cv?->value;
    }

    public function setCustomFieldValue(string $fieldName, ?string $value): void
    {
        $field = CustomField::where('name', $fieldName)->first();
        if (!$field) {
            return;
        }
        ClientCustomValue::updateOrCreate(
            ['client_id' => $this->id, 'custom_field_id' => $field->id],
            ['value' => $value]
        );
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, ?string $search)
    {
        if (empty($search)) {
            return $query;
        }
        $term = "%{$search}%";
        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', $term)
                ->orWhere('last_name', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('phone', 'like', $term);
        });
    }
}
