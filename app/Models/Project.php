<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'show_on_site',
        'site_description',
        'map_embed_url',
    ];

    protected $casts = [
        'show_on_site' => 'boolean',
    ];

    public function expenseItems(): HasMany
    {
        return $this->hasMany(ProjectExpenseItem::class, 'project_id')->orderBy('sort_order')->orderBy('id');
    }

    public function balanceTransactions(): HasMany
    {
        return $this->hasMany(BalanceTransaction::class, 'project_id');
    }

    public function apartments(): HasMany
    {
        $query = $this->hasMany(Apartment::class, 'project_id');
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            $query->orderByRaw('CAST(apartment_number AS UNSIGNED) ASC')->orderBy('apartment_number');
        } elseif ($driver === 'sqlite') {
            $query->orderByRaw('CAST(apartment_number AS INTEGER) ASC')->orderBy('apartment_number');
        } else {
            $query->orderBy('apartment_number');
        }
        return $query;
    }

    public function documentFields(): HasMany
    {
        return $this->hasMany(ProjectDocumentField::class, 'project_id')->orderBy('sort_order')->orderBy('id');
    }

    public function sitePhotos(): HasMany
    {
        return $this->hasMany(ProjectPhoto::class, 'project_id')->orderBy('sort_order')->orderBy('id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class, 'project_id')->latest();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id')->orderBy('status')->orderBy('sort_order')->orderBy('id');
    }

    public function clientProjectInvestments(): HasMany
    {
        return $this->hasMany(ClientProjectInvestment::class, 'project_id');
    }

    public function constructionStages(): HasMany
    {
        return $this->hasMany(ConstructionStage::class, 'project_id')->orderBy('sort_order')->orderBy('id');
    }
}
