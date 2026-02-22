<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        $projects = Project::where('show_on_site', true)
            ->with('sitePhotos')
            ->orderBy('name')
            ->get();
        return view('landing.index', compact('projects'));
    }

    public function lombard(): View
    {
        return view('landing.lombard.index');
    }

    public function project(Project $project): View
    {
        if (!$project->show_on_site) {
            abort(404);
        }
        $driver = DB::getDriverName();
        $project->load(['sitePhotos', 'apartments' => function ($q) use ($driver) {
            $q->where('status', Apartment::STATUS_AVAILABLE);
            if ($driver === 'mysql') {
                $q->orderByRaw('CAST(apartment_number AS UNSIGNED) ASC')->orderBy('apartment_number');
            } elseif ($driver === 'sqlite') {
                $q->orderByRaw('CAST(apartment_number AS INTEGER) ASC')->orderBy('apartment_number');
            } else {
                $q->orderBy('apartment_number');
            }
        }]);
        return view('landing.project', compact('project'));
    }
}
