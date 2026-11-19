<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;

class AdminApplicationsController extends Controller
{
    public function mediaHouse(Request $request)
    {
        $q = Application::query()
            ->with(['applicant'])
            ->where('application_type', 'registration')
            // Only show applications approved for payment or further
            ->whereNotIn('status', [Application::DRAFT, Application::SUBMITTED, Application::OFFICER_REVIEW, Application::CORRECTION_REQUESTED])
            ->latest();

        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }

        $applications = $q->paginate(20)->withQueryString();

        return view('admin.applications.index', [
            'title' => 'Media House Registrations',
            'applications' => $applications,
            'type' => 'registration',
        ]);
    }

    public function accreditation(Request $request)
    {
        $q = Application::query()
            ->with(['applicant'])
            ->where('application_type', 'accreditation')
            // Only show applications approved for payment or further
            ->whereNotIn('status', [Application::DRAFT, Application::SUBMITTED, Application::OFFICER_REVIEW, Application::CORRECTION_REQUESTED])
            ->latest();

        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }

        $applications = $q->paginate(20)->withQueryString();

        return view('admin.applications.index', [
            'title' => 'Media Practitioners Accreditation',
            'applications' => $applications,
            'type' => 'accreditation',
        ]);
    }

    public function show(Application $application)
    {
        $application->load(['applicant', 'documents', 'messages', 'workflowLogs', 'comments.user']);

        return view('admin.applications.show', compact('application'));
    }
}
