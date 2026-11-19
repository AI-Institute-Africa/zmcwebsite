@component('mail::message')
# Registration Fee Required

Dear {{ $application->applicantUser->first_name ?? 'Applicant' }},

Your media house application (Reference: **{{ $application->reference }}**) has been reviewed and approved by the ZMC Registrar.

To finalize your registration, please log in to the portal and proceed to pay your registration fee. Once the fee is paid and verified, your registration will be complete.

@component('mail::button', ['url' => url('/')])
Log In to Portal
@endcomponent

Thank you,<br>
{{ config('app.name') }}
@endcomponent
