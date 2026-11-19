<?php

namespace App\Services;

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApplicationAssignmentService
{
    /**
     * Assign application to an Accreditation Officer based on collection_region.
     * Strategy: pick least-loaded officer for that region.
     */
    public function assign(Application $application): ?User
    {
        return DB::transaction(function () use ($application) {
            if ($application->assigned_officer_id) {
                return $application->assignedOfficer;
            }

            $regionCode = strtolower($application->collection_region);
            
            // Find the active region record
            $regionRecord = \App\Models\Region::query()
                ->where('code', $regionCode)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                })
                ->first();

            if (!$regionRecord) {
                return null; // Region not active or expired
            }

            // Pick least-loaded officer assigned to THIS region
            $officer = User::query()
                ->whereHas('assignedRegions', function ($q) use ($regionRecord) {
                    $q->where('regions.id', $regionRecord->id);
                })
                ->withCount(['assignedApplications as open_assigned_count' => function ($q) {
                    $q->whereIn('status', [
                        Application::SUBMITTED, 
                        Application::OFFICER_REVIEW, 
                        Application::CORRECTION_REQUESTED, 
                        Application::RETURNED_TO_OFFICER
                    ]);
                }])
                ->orderBy('open_assigned_count', 'asc')
                ->orderBy('id', 'asc')
                ->first();

            if (!$officer) {
                return null;
            }

            $application->update([
                'assigned_officer_id' => $officer->id,
                'assigned_at' => now(),
            ]);

            return $officer;
        });
    }
}
