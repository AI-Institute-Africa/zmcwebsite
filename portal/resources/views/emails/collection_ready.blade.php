@component('mail::message')
# Certificate Ready for Collection

Dear {{ $application->applicantUser->first_name ?? 'Applicant' }},

Your media house registration (Reference: **{{ $application->reference }}**) is now complete. 

Your physical certificate has been generated and is ready for collection.

Please collect your certificate from our main office at:
**ZMC Head Office, 7th Floor, Fidelity Life Tower, Raleigh Street, Harare**

Please bring a valid ID when collecting your certificate.

Thank you,<br>
{{ config('app.name') }}
@endcomponent
