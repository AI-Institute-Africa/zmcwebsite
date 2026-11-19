<h2>Application Awaiting Registrar Review</h2>

<p>Dear Registrar,</p>

<p>A Media House application (Reference: <strong>{{ $application->reference }}</strong>) has been reviewed and approved by an Accreditation Officer.</p>

<p>It is now in your queue and requires physical verification of the submitted documents.</p>

<p>
    <a href="{{ route('staff.registrar.applications.show', $application->id) }}"
       style="display:inline-block;padding:10px 18px;background:#176b4d;color:#fff;text-decoration:none;border-radius:6px;">
        View Application
    </a>
</p>

<p>Thank you,<br>{{ config('app.name') }}</p>
