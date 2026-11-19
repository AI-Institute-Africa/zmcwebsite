<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    private $knowledgeBase = [
        'about_zmc' => [
            'keywords' => ['zmc', 'zimbabwe media commission', 'what is zmc', 'about zmc', 'commission', 'who are you', 'tell me about'],
            'response' => "The Zimbabwe Media Commission (ZMC) is the statutory body established under Section 248 of the Constitution of Zimbabwe to uphold, promote, and develop freedom of the media.\n\n**Our Mandate:**\n- Register and accredit media practitioners\n- Register mass media services\n- Promote and enforce good practices in the media\n- Ensure fair competition among media houses\n- Encourage the development of media training and technology\n\n**Contact:**\n- Address: ZMC House, 108 Swan Drive, Alexandra Park, Harare\n- Phone: {{ORG_PUBLIC_PHONE}}\n- Email: {{ORG_ACCREDITATION_EMAIL}}\n- Website: {{ORG_WEBSITE_HOST}}"
        ],
        'accreditation_overview' => [
            'keywords' => ['accreditation', 'accredit', 'journalist', 'press card', 'how to become', 'become journalist', 'ap3', 'get accredited', 'media card', 'journalist card'],
            'response' => "**Media Practitioner Accreditation (AP3 Form)**\n\nTo practice media work in Zimbabwe, you need to be accredited by ZMC. Here's how:\n\n**Steps:**\n1. Create an account on our portal\n2. Complete the AP3 application form\n3. Upload required documents\n4. Pay the application fee\n5. Await verification and approval\n6. Collect your press card from your chosen regional office\n\n**Types of Accreditation:**\n- **Local Media Practitioners** - Zimbabwean citizens working for local media\n- **Foreign Media Practitioners** - International media practitioners\n\nFees depend on the application category and current statutory schedule. Check the amount shown in your application.\n\nType 'documents for accreditation' to see required documents."
        ],
        'accreditation_documents' => [
            'keywords' => ['documents accreditation', 'documents journalist', 'accreditation documents', 'ap3 documents', 'what documents press card', 'requirements accreditation', 'what do i need', 'required documents'],
            'response' => "**Required Documents for Media Practitioner Accreditation (AP3):**\n\n**For Local Media Practitioners:**\n1. Certified copy of National ID\n2. Passport-size photograph (colored, recent)\n3. Proof of employment from media house\n4. Educational certificates (journalism/mass communication preferred)\n5. CV/Resume\n6. Police clearance certificate\n\n**For Foreign Media Practitioners:**\n1. Valid passport (certified copy)\n2. Passport-size photograph (colored, recent)\n3. Letter of assignment from media organization\n4. Immigration/work permit documents\n5. CV/Resume\n6. Police clearance from country of origin\n\n**File Requirements:**\n- PDF or image format (JPEG, PNG)\n- Follow the upload size limit shown on the application form\n- Clear and legible copies"
        ],
        'registration_overview' => [
            'keywords' => ['register', 'registration', 'media house', 'ap1', 'register media', 'start media', 'media company', 'broadcast', 'newspaper', 'online media', 'radio', 'television', 'tv station'],
            'response' => "**Media House Registration (AP1 Form)**\n\nAll mass media services in Zimbabwe must be registered with ZMC. Here's the process:\n\n**Steps:**\n1. Create an account on our portal\n2. Complete the AP1 registration form\n3. Upload required annexures (supporting documents)\n4. Pay the registration fee\n5. Await verification and site inspection\n6. Receive your registration certificate\n\n**Categories:**\n- Print Media (newspapers, magazines)\n- Broadcasting (radio, television)\n- Online/Digital Media\n- News Agencies\n\nFees are set by the current statutory schedule. Check the amount shown in your application before paying.\n\nType 'documents for registration' to see required annexures."
        ],
        'registration_documents' => [
            'keywords' => ['documents registration', 'documents media house', 'registration documents', 'ap1 documents', 'annexures', 'requirements registration', 'what documents register', 'media house requirements'],
            'response' => "**Required Annexures for Media House Registration (AP1):**\n\n**Annexure A - Company Documents:**\n- Certificate of Incorporation (CR14)\n- Memorandum and Articles of Association\n- Tax clearance certificate (ZIMRA)\n\n**Annexure B - Ownership Declaration:**\n- Directors' certified ID copies\n- Shareholding structure\n- Declaration of beneficial ownership\n\n**Annexure C - Operational Plan:**\n- Business plan/proposal\n- Editorial policy document\n- Code of conduct\n\n**Annexure D - Technical Requirements:**\n- For broadcasters: Frequency allocation letter (POTRAZ)\n- Studio/office location details\n- Equipment specifications\n\n**Annexure E - Financial Capacity:**\n- Bank statements (3 months)\n- Proof of capital\n- Financial projections\n\n**File Requirements:**\n- PDF format preferred\n- Follow the upload size limit shown on the application form"
        ],
        'fees' => [
            'keywords' => ['fees', 'cost', 'how much', 'price', 'payment', 'pay', 'charges', 'money', 'amount'],
            'response' => "Fees depend on the application type and current statutory schedule. Review the amount shown in your application before paying. For help, contact {{ORG_ACCREDITATION_EMAIL}}."
        ],
        'paynow' => [
            'keywords' => ['paynow', 'ecocash', 'onemoney', 'mobile money', 'online payment', 'pay online', 'mobile payment'],
            'response' => "**Paynow Payment Guide:**\n\n**How to Pay via Paynow:**\n1. Select 'Pay Now' on your application dashboard\n2. Choose your preferred payment method:\n   - EcoCash\n   - OneMoney\n   - Bank Card (Visa/MasterCard)\n3. Enter your payment details\n4. Confirm the transaction\n5. Wait for payment confirmation\n\n**After Payment:**\n- Follow the payment status shown in your portal\n- Check for payment confirmation and status updates\n\n**Payment Issues?**\n- Keep your transaction reference number\n- Contact: {{ORG_ACCREDITATION_EMAIL}}\n- Phone: {{ORG_PUBLIC_PHONE}}"
        ],
        'offices' => [
            'keywords' => ['office', 'location', 'where', 'address', 'collect', 'regional', 'harare', 'bulawayo', 'pick up', 'collection point'],
            'response' => "**ZMC Regional Offices:**\n\n**Head Office - Harare:**\n- Address: ZMC House, 108 Swan Drive, Alexandra Park, Harare\n- Phone: {{ORG_PUBLIC_PHONE}}\n- Hours: Mon-Fri 8:00 AM - 4:30 PM\n\n**Bulawayo Regional Office:**\n- Address: 17 Rylanders Court, Windsor Park Complex, 16th Avenue, Famona, Bulawayo\n- Phone: +263 292 123 456\n\n**Services at Regional Offices:**\n- Press card collection\n- Certificate collection\n- Application assistance\n- Document verification"
        ],
        'track' => [
            'keywords' => ['track', 'status', 'application status', 'check', 'progress', 'where is my', 'how long', 'when will', 'waiting', 'processing time'],
            'response' => "**Track Your Application:**\n\nYou can track your application status through:\n\n1. **Online Portal:**\n   - Log in to your account\n   - Go to Dashboard\n   - View 'My Applications' section\n\n2. **Application Statuses:**\n   - **Draft** - Not yet submitted\n   - **Submitted** - Received, awaiting review\n   - **Under Review** - Being processed by officer\n   - **Pending Payment** - Approved, awaiting fee\n   - **Paid** - Payment confirmed\n   - **Approved** - Processing complete\n   - **Ready for Collection** - Card/Certificate ready\n   - **Issued** - Collected\n\nProcessing times vary by application and verification needs. Track the current status in your portal.\n\nFor urgent inquiries: {{ORG_ACCREDITATION_EMAIL}}"
        ],
        'renewal' => [
            'keywords' => ['renew', 'renewal', 'expire', 'expired', 'extend', 'ap5', 'validity', 'expiring', 'renewing'],
            'response' => "Renewal requirements, validity periods, deadlines, and any applicable charges follow the current statutory rules and the record for your licence or card. Sign in to the portal, open Renewals, and follow the dates and instructions shown for your record. For help, contact {{ORG_ACCREDITATION_EMAIL}}."
        ],
        'replacement' => [
            'keywords' => ['replacement', 'lost', 'stolen', 'damaged', 'new card', 'replace card', 'misplaced'],
            'response' => "Replacement requirements and charges depend on the document and the reason for replacement. Sign in to the portal and follow the current replacement instructions shown for your account. For help, contact {{ORG_ACCREDITATION_EMAIL}}."
        ],
        'contact' => [
            'keywords' => ['contact', 'email', 'phone', 'call', 'reach', 'talk', 'human', 'help', 'support', 'speak', 'complaint', 'complain'],
            'response' => "**Contact Zimbabwe Media Commission:**\n\n**General Inquiries:**\n- Email: {{ORG_ACCREDITATION_EMAIL}}\n- Phone: {{ORG_PUBLIC_PHONE}}\n\n**Specific Departments:**\n- Accreditation: {{ORG_ACCREDITATION_EMAIL}}\n- Registration: {{ORG_ACCREDITATION_EMAIL}}\n- Accounts/Payments: {{ORG_ACCREDITATION_EMAIL}}\n- Complaints: {{ORG_ACCREDITATION_EMAIL}}\n\n**Office Hours:**\n- Monday to Friday: 8:00 AM - 4:30 PM\n- Closed on weekends and public holidays\n\n**Social Media:**\n- Twitter: @ZMCZimbabwe\n- Facebook: Zimbabwe Media Commission\n\nFor immediate assistance, please email {{ORG_ACCREDITATION_EMAIL}} with your query and application reference number."
        ],
        'faq_time' => [
            'keywords' => ['how long does it take', 'processing time', 'turnaround', 'when ready', 'wait time'],
            'response' => "**Processing Times FAQ:**\n\nProcessing time depends on the completeness of your application and any required verification. Check the portal for the latest status.\n\n**Q: Why is my application taking longer?**\nPossible reasons:\n- Incomplete documents\n- Payment not yet confirmed\n- High volume of applications\n- Additional verification required\n\n**Q: Can I expedite my application?**\nA: Urgent processing is available in special cases. Contact {{ORG_ACCREDITATION_EMAIL}} with your reasons.\n\n**Q: How do I know when it's ready?**\nA: You'll receive SMS and email notification. Also check your portal dashboard."
        ],
        'faq_eligibility' => [
            'keywords' => ['eligible', 'qualify', 'can i apply', 'requirements to apply', 'who can apply', 'qualifications'],
            'response' => "**Eligibility FAQ:**\n\n**Q: Who can apply for media practitioner accreditation?**\nA: Any person practicing or intending to practice media work in Zimbabwe, including:\n- Full-time media practitioners\n- Freelance media practitioners\n- Photographers and videographers\n- Foreign correspondents\n\n**Q: Do I need a journalism degree?**\nA: Not mandatory, but relevant qualifications are considered. Experience in media is also accepted.\n\n**Q: Can a student apply?**\nA: Students on internship can apply with a letter from their institution and media house.\n\n**Q: Who can register a media house?**\nA: Any registered company or individual intending to operate a mass media service in Zimbabwe.\n\nFor more info, email: {{ORG_ACCREDITATION_EMAIL}}"
        ],
        'faq_changes' => [
            'keywords' => ['change', 'update', 'modify', 'edit', 'correct', 'wrong information', 'mistake', 'amendment'],
            'response' => "**Changes & Amendments FAQ:**\n\n**Q: Can I update my information after submission?**\nA: Contact our office to request amendments before approval.\n\n**Q: How do I change my employer?**\nA: Submit a change of employer form with new employment letter.\n\n**Q: What if my name changed?**\nA: Provide legal documentation (marriage certificate, deed poll) and apply for card replacement.\n\n**Q: Can I change my collection point?**\nA: Yes, contact us before card production to change regional office.\n\n**Q: How do I update company details?**\nA: Submit updated company documents and request amendment.\n\nFor all changes, email: {{ORG_ACCREDITATION_EMAIL}} with your reference number."
        ],
        'greeting' => [
            'keywords' => ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening', 'greetings', 'howdy'],
            'response' => "Hello! Welcome to the ZMC Assistant. I can help you with:\n\n- **Media Practitioner Accreditation** - How to get your press card\n- **Media House Registration** - How to register your media company\n- **Required Documents** - What you need to submit\n- **Fees & Payments** - Costs and payment methods\n- **Office Locations** - Where to collect your card/certificate\n- **Application Tracking** - Check your application status\n- **Renewals & Replacements** - Extend validity or replace cards\n\nJust type your question or select a topic above!"
        ],
        'thanks' => [
            'keywords' => ['thank', 'thanks', 'thank you', 'appreciate', 'helpful', 'great'],
            'response' => "You're welcome! I'm glad I could help.\n\nIf you have any more questions about:\n- Media practitioner accreditation\n- Media house registration\n- Fees and payments\n- Application status\n\nFeel free to ask anytime!\n\n**Need further assistance?**\nEmail: {{ORG_ACCREDITATION_EMAIL}}\nPhone: {{ORG_PUBLIC_PHONE}}"
        ],
    ];

    public function message(Request $request)
    {
        $userMessage = strtolower(trim($request->input('message', '')));
        
        if (empty($userMessage)) {
            return response()->json([
                'response' => "Please type a question and I'll do my best to help you.",
                'matched' => false
            ]);
        }

        $bestMatch = null;
        $highestScore = 0;

        foreach ($this->knowledgeBase as $topic => $data) {
            $score = 0;
            foreach ($data['keywords'] as $keyword) {
                if (str_contains($userMessage, strtolower($keyword))) {
                    $score += strlen($keyword);
                }
            }
            
            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $data['response'];
            }
        }

        if ($bestMatch && $highestScore >= 3) {
            return response()->json([
                'response' => $this->withConfiguredContacts($bestMatch),
                'matched' => true
            ]);
        }

        return response()->json([
            'response' => "I'm sorry, I don't have specific information about that topic. Here are some things I can help with:\n\n- How to get accredited as a media practitioner\n- How to register a media house\n- Required documents for applications\n- Fees and payment information\n- Office locations and contact details\n- Application tracking and status\n- Renewals and replacements\n\n**For further assistance, please contact:**\nEmail: {{ORG_ACCREDITATION_EMAIL}}\nPhone: {{ORG_PUBLIC_PHONE}}\n\nOur team will be happy to help you!",
            'matched' => false
        ]);
    }

    private function withConfiguredContacts(string $response): string
    {
        return str_replace(
            ['{{ORG_ACCREDITATION_EMAIL}}', '{{ORG_PUBLIC_PHONE}}', '{{ORG_WEBSITE_HOST}}'],
            [
                (string) config('organization.accreditation_email', ''),
                (string) config('organization.public_phone', ''),
                (string) parse_url((string) config('app.website_url', ''), PHP_URL_HOST),
            ],
            $response
        );
    }
}
