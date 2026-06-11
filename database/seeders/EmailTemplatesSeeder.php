<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\EmailLog;
use App\Infrastructure\Persistence\Models\EmailTemplate;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Seeder;

class EmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $agency   = Agency::where('slug', 'demo')->firstOrFail();
        $agent    = User::where('email', 'agent@villacrm.app')->firstOrFail();
        $contacts = Contact::where('agency_id', $agency->id)->get();

        $this->seedTemplates($agency->id);
        $this->seedEmailLogs($agency->id, $agent, $contacts);
    }

    // ── Email Templates ────────────────────────────────────────────────────────

    private function seedTemplates(int $agencyId): void
    {
        $templates = [

            // ── LEAD CATEGORY ──────────────────────────────────────────────────

            [
                'name'       => 'New Lead Welcome',
                'slug'       => 'lead-welcome',
                'subject'    => 'Welcome to Demo Agency, {{first_name}}!',
                'category'   => 'lead',
                'variables'  => ['first_name', 'agent_name', 'agent_phone', 'agent_email', 'agency_name'],
                'body_html'  => <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1e293b">
  <div style="background:#3b82f6;padding:32px 24px;text-align:center">
    <h1 style="color:#fff;margin:0;font-size:24px">Welcome to Demo Agency</h1>
  </div>
  <div style="padding:32px 24px;background:#f8fafc">
    <p>Hi {{first_name}},</p>
    <p>Thank you for reaching out to Demo Agency — Lagos & Abuja's premier property specialists.</p>
    <p>Your enquiry has been assigned to <strong>{{agent_name}}</strong>, who will be in touch within the next 24 hours to discuss your property needs.</p>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin:24px 0">
      <p style="margin:0 0 8px;font-weight:bold">Your Dedicated Agent</p>
      <p style="margin:0;color:#64748b">{{agent_name}}</p>
      <p style="margin:0;color:#64748b">📞 {{agent_phone}}</p>
      <p style="margin:0;color:#64748b">✉️ {{agent_email}}</p>
    </div>
    <p>In the meantime, browse our <a href="#" style="color:#3b82f6">latest listings</a>.</p>
    <p>Kind regards,<br><strong>{{agency_name}} Team</strong></p>
  </div>
  <div style="padding:16px 24px;text-align:center;font-size:12px;color:#94a3b8">
    Demo Agency · Lagos, Nigeria · <a href="#" style="color:#94a3b8">Unsubscribe</a>
  </div>
</div>
HTML,
                'body_text'  => "Hi {{first_name}},\n\nThank you for reaching out to Demo Agency. Your enquiry has been assigned to {{agent_name}} ({{agent_phone}} | {{agent_email}}), who will contact you within 24 hours.\n\nKind regards,\n{{agency_name}} Team",
            ],

            [
                'name'       => 'Lead Qualification Follow-Up',
                'slug'       => 'lead-qualification',
                'subject'    => 'Quick question about your property search, {{first_name}}',
                'category'   => 'lead',
                'variables'  => ['first_name', 'agent_name', 'property_type', 'budget', 'location', 'agent_phone'],
                'body_html'  => <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1e293b">
  <div style="padding:32px 24px">
    <p>Hi {{first_name}},</p>
    <p>I'm {{agent_name}} from Demo Agency. You recently enquired about {{property_type}} in {{location}}.</p>
    <p>To help me find the perfect property for you, could you confirm a few details?</p>
    <ul>
      <li>What is your preferred budget range? (You indicated ~₦{{budget}})</li>
      <li>Are you buying for personal use or investment?</li>
      <li>What is your preferred timeline to move?</li>
    </ul>
    <p>You can reply to this email or call me directly on <strong>{{agent_phone}}</strong>.</p>
    <p>Looking forward to helping you find your ideal property.</p>
    <p>Best regards,<br>{{agent_name}}</p>
  </div>
</div>
HTML,
                'body_text'  => "Hi {{first_name}},\n\nI'm {{agent_name}} from Demo Agency. To help find your ideal {{property_type}} in {{location}}, I have a few quick questions:\n\n1. Budget range (you indicated ~₦{{budget}})\n2. Personal use or investment?\n3. Timeline to move?\n\nCall me on {{agent_phone}} or reply here.\n\nBest,\n{{agent_name}}",
            ],

            // ── LISTING CATEGORY ───────────────────────────────────────────────

            [
                'name'       => 'New Listing Alert',
                'slug'       => 'new-listing-alert',
                'subject'    => 'New {{property_type}} matching your search in {{location}}',
                'category'   => 'listing',
                'variables'  => ['first_name', 'property_type', 'location', 'address', 'bedrooms', 'bathrooms', 'price', 'listing_url', 'agent_name'],
                'body_html'  => <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1e293b">
  <div style="background:#0f172a;padding:24px;text-align:center">
    <h2 style="color:#fff;margin:0">New Property Alert</h2>
    <p style="color:#94a3b8;margin:4px 0 0">Demo Agency</p>
  </div>
  <div style="padding:24px">
    <p>Hi {{first_name}},</p>
    <p>A new {{property_type}} has just been listed in {{location}} that matches your search criteria.</p>
    <div style="border:2px solid #3b82f6;border-radius:12px;overflow:hidden;margin:24px 0">
      <div style="background:#eff6ff;padding:16px">
        <h3 style="margin:0 0 4px;color:#1e40af">{{address}}</h3>
        <p style="margin:0;color:#3b82f6;font-size:20px;font-weight:bold">₦{{price}}</p>
      </div>
      <div style="padding:16px;display:flex;gap:16px">
        <span>🛏 {{bedrooms}} Bedrooms</span>
        <span>🛁 {{bathrooms}} Bathrooms</span>
      </div>
    </div>
    <div style="text-align:center">
      <a href="{{listing_url}}" style="display:inline-block;background:#3b82f6;color:#fff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:bold">View Full Listing</a>
    </div>
    <p style="margin-top:24px">Questions? Contact {{agent_name}} directly.</p>
  </div>
</div>
HTML,
                'body_text'  => "Hi {{first_name}},\n\nNew {{property_type}} in {{location}}:\n{{address}}\n₦{{price}} | {{bedrooms}} bed | {{bathrooms}} bath\n\nView: {{listing_url}}\n\nContact: {{agent_name}}",
            ],

            [
                'name'       => 'Monthly Seller Report',
                'slug'       => 'seller-report-monthly',
                'subject'    => 'Your Property Report — {{property_address}} ({{month}})',
                'category'   => 'listing',
                'variables'  => ['seller_name', 'property_address', 'month', 'views_count', 'enquiries_count', 'viewings_count', 'days_on_market', 'agent_name', 'agent_phone'],
                'body_html'  => <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1e293b">
  <div style="background:#1e293b;padding:24px">
    <h2 style="color:#fff;margin:0">Monthly Seller Report</h2>
    <p style="color:#94a3b8;margin:4px 0 0">{{property_address}}</p>
  </div>
  <div style="padding:24px">
    <p>Dear {{seller_name}},</p>
    <p>Here is your property performance summary for <strong>{{month}}</strong>.</p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:24px 0">
      <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:16px;text-align:center">
        <div style="font-size:28px;font-weight:bold;color:#16a34a">{{views_count}}</div>
        <div style="color:#64748b;font-size:14px">Portal Views</div>
      </div>
      <div style="background:#eff6ff;border:1px solid #93c5fd;border-radius:8px;padding:16px;text-align:center">
        <div style="font-size:28px;font-weight:bold;color:#1d4ed8">{{enquiries_count}}</div>
        <div style="color:#64748b;font-size:14px">Enquiries</div>
      </div>
      <div style="background:#fefce8;border:1px solid #fde047;border-radius:8px;padding:16px;text-align:center">
        <div style="font-size:28px;font-weight:bold;color:#ca8a04">{{viewings_count}}</div>
        <div style="color:#64748b;font-size:14px">Viewings</div>
      </div>
      <div style="background:#fdf4ff;border:1px solid #e9d5ff;border-radius:8px;padding:16px;text-align:center">
        <div style="font-size:28px;font-weight:bold;color:#7c3aed">{{days_on_market}}</div>
        <div style="color:#64748b;font-size:14px">Days on Market</div>
      </div>
    </div>
    <p>Please contact {{agent_name}} on {{agent_phone}} to discuss any adjustments to the listing strategy.</p>
    <p>Kind regards,<br>{{agent_name}}</p>
  </div>
</div>
HTML,
                'body_text'  => "Dear {{seller_name}},\n\nProperty report for {{property_address}} — {{month}}:\n- Portal Views: {{views_count}}\n- Enquiries: {{enquiries_count}}\n- Viewings: {{viewings_count}}\n- Days on Market: {{days_on_market}}\n\nContact: {{agent_name}} | {{agent_phone}}",
            ],

            // ── OFFER CATEGORY ─────────────────────────────────────────────────

            [
                'name'       => 'Offer Received — Seller Notification',
                'slug'       => 'offer-received-seller',
                'subject'    => 'Offer Received on {{property_address}} — Action Required',
                'category'   => 'offer',
                'variables'  => ['seller_name', 'property_address', 'offer_amount', 'buyer_name', 'expiry_date', 'deposit_amount', 'occupation_date', 'conditions', 'agent_name', 'agent_phone'],
                'body_html'  => <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1e293b">
  <div style="background:#f59e0b;padding:24px;text-align:center">
    <h2 style="color:#fff;margin:0">⚡ Offer Received</h2>
  </div>
  <div style="padding:24px">
    <p>Dear {{seller_name}},</p>
    <p>We have received a formal offer on your property at <strong>{{property_address}}</strong>.</p>
    <div style="background:#fffbeb;border:2px solid #f59e0b;border-radius:8px;padding:16px;margin:20px 0">
      <h3 style="margin:0 0 12px;color:#92400e">Offer Details</h3>
      <table style="width:100%;border-collapse:collapse">
        <tr><td style="padding:6px 0;color:#64748b">Offer Amount</td><td style="font-weight:bold;color:#1e293b">₦{{offer_amount}}</td></tr>
        <tr><td style="padding:6px 0;color:#64748b">Buyer</td><td style="color:#1e293b">{{buyer_name}}</td></tr>
        <tr><td style="padding:6px 0;color:#64748b">Deposit</td><td style="color:#1e293b">₦{{deposit_amount}}</td></tr>
        <tr><td style="padding:6px 0;color:#64748b">Proposed Occupation</td><td style="color:#1e293b">{{occupation_date}}</td></tr>
        <tr><td style="padding:6px 0;color:#64748b">Offer Expires</td><td style="font-weight:bold;color:#dc2626">{{expiry_date}}</td></tr>
      </table>
    </div>
    <p><strong>Conditions:</strong> {{conditions}}</p>
    <p>⚠️ This offer expires on <strong>{{expiry_date}}</strong>. Please respond urgently.</p>
    <p>Call {{agent_name}} on <strong>{{agent_phone}}</strong> to accept, counter, or reject.</p>
    <p>Kind regards,<br>{{agent_name}}<br>Demo Agency</p>
  </div>
</div>
HTML,
                'body_text'  => "Dear {{seller_name}},\n\nOffer received on {{property_address}}:\nAmount: ₦{{offer_amount}}\nBuyer: {{buyer_name}}\nDeposit: ₦{{deposit_amount}}\nOccupation: {{occupation_date}}\nExpires: {{expiry_date}}\n\nConditions: {{conditions}}\n\nContact {{agent_name}}: {{agent_phone}}",
            ],

            [
                'name'       => 'Offer Accepted — Buyer Confirmation',
                'slug'       => 'offer-accepted-buyer',
                'subject'    => 'Congratulations! Your offer on {{property_address}} has been accepted',
                'category'   => 'offer',
                'variables'  => ['buyer_name', 'property_address', 'offer_amount', 'occupation_date', 'next_steps', 'agent_name', 'agent_phone'],
                'body_html'  => <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1e293b">
  <div style="background:#16a34a;padding:32px 24px;text-align:center">
    <h1 style="color:#fff;margin:0;font-size:28px">🎉 Offer Accepted!</h1>
  </div>
  <div style="padding:32px 24px">
    <p>Dear {{buyer_name}},</p>
    <p>Congratulations! Your offer of <strong>₦{{offer_amount}}</strong> on <strong>{{property_address}}</strong> has been accepted by the seller.</p>
    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:20px;margin:20px 0">
      <h3 style="margin:0 0 8px;color:#166534">Next Steps</h3>
      <p style="margin:0;color:#166534">{{next_steps}}</p>
    </div>
    <p>Your planned occupation date is <strong>{{occupation_date}}</strong>.</p>
    <p>{{agent_name}} will be in touch within 24 hours to guide you through the next steps. You can also call <strong>{{agent_phone}}</strong> at any time.</p>
    <p>Welcome to your new home!<br><br>Kind regards,<br>{{agent_name}}<br>Demo Agency</p>
  </div>
</div>
HTML,
                'body_text'  => "Dear {{buyer_name}},\n\nCongratulations! Your offer of ₦{{offer_amount}} on {{property_address}} has been accepted!\n\nOccupation: {{occupation_date}}\nNext Steps: {{next_steps}}\n\nContact: {{agent_name}} | {{agent_phone}}",
            ],

            // ── TRANSACTION CATEGORY ───────────────────────────────────────────

            [
                'name'       => 'FICA Documents Request',
                'slug'       => 'fica-documents-request',
                'subject'    => 'Action Required: FICA Documents for {{property_address}}',
                'category'   => 'transaction',
                'variables'  => ['buyer_name', 'property_address', 'document_list', 'submission_deadline', 'agent_name', 'agent_email'],
                'body_html'  => <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1e293b">
  <div style="background:#7c3aed;padding:24px">
    <h2 style="color:#fff;margin:0">FICA Compliance — Documents Required</h2>
  </div>
  <div style="padding:24px">
    <p>Dear {{buyer_name}},</p>
    <p>To comply with the Financial Intelligence Centre Act (FICA), we are required to collect certain documents before proceeding with the transaction for <strong>{{property_address}}</strong>.</p>
    <div style="background:#fdf4ff;border:1px solid #d8b4fe;border-radius:8px;padding:16px;margin:20px 0">
      <h3 style="margin:0 0 8px;color:#6b21a8">Documents Required</h3>
      <p style="white-space:pre-line;margin:0">{{document_list}}</p>
    </div>
    <p>Please submit all documents by <strong>{{submission_deadline}}</strong> to avoid delays.</p>
    <p>Submit via email to <a href="mailto:{{agent_email}}" style="color:#7c3aed">{{agent_email}}</a> or bring certified copies to our office.</p>
    <p>Kind regards,<br>{{agent_name}}<br>Demo Agency Compliance Team</p>
  </div>
</div>
HTML,
                'body_text'  => "Dear {{buyer_name}},\n\nFICA documents required for {{property_address}}:\n\n{{document_list}}\n\nDeadline: {{submission_deadline}}\nSubmit to: {{agent_email}}\n\n{{agent_name}}",
            ],

            [
                'name'       => 'Transaction Progress Update',
                'slug'       => 'transaction-progress',
                'subject'    => 'Transaction Update: {{property_address}} — {{current_stage}}',
                'category'   => 'transaction',
                'variables'  => ['client_name', 'property_address', 'current_stage', 'stage_description', 'estimated_completion', 'agent_name', 'agent_phone'],
                'body_html'  => <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1e293b">
  <div style="background:#1e293b;padding:24px">
    <h2 style="color:#fff;margin:0">Transaction Update</h2>
    <p style="color:#94a3b8;margin:4px 0 0">{{property_address}}</p>
  </div>
  <div style="padding:24px">
    <p>Dear {{client_name}},</p>
    <p>Here is a progress update on your property transaction.</p>
    <div style="border-left:4px solid #3b82f6;padding:12px 16px;margin:20px 0;background:#eff6ff">
      <p style="margin:0;font-weight:bold;color:#1d4ed8">Current Stage: {{current_stage}}</p>
      <p style="margin:8px 0 0;color:#374151">{{stage_description}}</p>
    </div>
    <p><strong>Estimated Completion:</strong> {{estimated_completion}}</p>
    <p>Do not hesitate to contact {{agent_name}} on {{agent_phone}} if you have any questions.</p>
    <p>Kind regards,<br>{{agent_name}}</p>
  </div>
</div>
HTML,
                'body_text'  => "Dear {{client_name}},\n\nTransaction update for {{property_address}}:\nCurrent Stage: {{current_stage}}\n{{stage_description}}\n\nEstimated completion: {{estimated_completion}}\n\nContact: {{agent_name}} | {{agent_phone}}",
            ],

            // ── LEASE CATEGORY ─────────────────────────────────────────────────

            [
                'name'       => 'Rent Payment Receipt',
                'slug'       => 'rent-payment-receipt',
                'subject'    => 'Payment Receipt — {{reference}} ({{month}})',
                'category'   => 'lease',
                'variables'  => ['tenant_name', 'property_address', 'reference', 'amount', 'month', 'payment_method', 'paid_date', 'agent_name'],
                'body_html'  => <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1e293b">
  <div style="background:#16a34a;padding:24px;text-align:center">
    <h2 style="color:#fff;margin:0">✅ Payment Received</h2>
  </div>
  <div style="padding:24px">
    <p>Dear {{tenant_name}},</p>
    <p>This is your official receipt for your rental payment.</p>
    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:20px;margin:20px 0">
      <table style="width:100%;border-collapse:collapse">
        <tr><td style="padding:6px 0;color:#64748b">Reference</td><td style="font-weight:bold;font-family:monospace">{{reference}}</td></tr>
        <tr><td style="padding:6px 0;color:#64748b">Property</td><td>{{property_address}}</td></tr>
        <tr><td style="padding:6px 0;color:#64748b">Period</td><td>{{month}}</td></tr>
        <tr><td style="padding:6px 0;color:#64748b">Amount Paid</td><td style="font-weight:bold;font-size:18px;color:#16a34a">₦{{amount}}</td></tr>
        <tr><td style="padding:6px 0;color:#64748b">Method</td><td>{{payment_method}}</td></tr>
        <tr><td style="padding:6px 0;color:#64748b">Date</td><td>{{paid_date}}</td></tr>
      </table>
    </div>
    <p>Please retain this receipt for your records.</p>
    <p>Kind regards,<br>{{agent_name}}<br>Demo Agency Property Management</p>
  </div>
</div>
HTML,
                'body_text'  => "Dear {{tenant_name}},\n\nPayment Receipt\nRef: {{reference}}\nProperty: {{property_address}}\nPeriod: {{month}}\nAmount: ₦{{amount}}\nMethod: {{payment_method}}\nDate: {{paid_date}}\n\n{{agent_name}}",
            ],

            [
                'name'       => 'Lease Expiry Reminder',
                'slug'       => 'lease-expiry-reminder',
                'subject'    => 'Your lease expires in {{days_remaining}} days — {{property_address}}',
                'category'   => 'lease',
                'variables'  => ['tenant_name', 'property_address', 'expiry_date', 'days_remaining', 'renewal_amount', 'agent_name', 'agent_phone'],
                'body_html'  => <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1e293b">
  <div style="background:#f59e0b;padding:24px;text-align:center">
    <h2 style="color:#fff;margin:0">⏰ Lease Expiry Notice</h2>
  </div>
  <div style="padding:24px">
    <p>Dear {{tenant_name}},</p>
    <p>This is a friendly reminder that your lease for <strong>{{property_address}}</strong> expires on <strong>{{expiry_date}}</strong> — that is in <strong>{{days_remaining}} days</strong>.</p>
    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:16px;margin:20px 0">
      <p style="margin:0;font-weight:bold">Renewal Option Available</p>
      <p style="margin:8px 0 0">We would love to have you stay! Your renewal rental will be <strong>₦{{renewal_amount}}/month</strong>.</p>
    </div>
    <p>To renew or discuss your options, please contact <strong>{{agent_name}}</strong> on <strong>{{agent_phone}}</strong> as soon as possible.</p>
    <p>Kind regards,<br>{{agent_name}}</p>
  </div>
</div>
HTML,
                'body_text'  => "Dear {{tenant_name}},\n\nYour lease for {{property_address}} expires on {{expiry_date}} ({{days_remaining}} days).\n\nRenewal option: ₦{{renewal_amount}}/month.\n\nContact: {{agent_name}} | {{agent_phone}}",
            ],

            // ── MARKETING CATEGORY ─────────────────────────────────────────────

            [
                'name'       => 'Monthly Property Newsletter',
                'slug'       => 'monthly-newsletter',
                'subject'    => 'Demo Agency Market Update — {{month}} {{year}}',
                'category'   => 'marketing',
                'variables'  => ['first_name', 'month', 'year', 'market_insight', 'featured_listings', 'agent_name'],
                'body_html'  => <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1e293b">
  <div style="background:linear-gradient(135deg,#1e293b,#3b82f6);padding:32px 24px;text-align:center">
    <h1 style="color:#fff;margin:0">Demo Agency</h1>
    <p style="color:#bfdbfe;margin:4px 0 0">Market Update · {{month}} {{year}}</p>
  </div>
  <div style="padding:24px">
    <p>Hi {{first_name}},</p>
    <h2 style="color:#1e40af">Market Insights</h2>
    <p>{{market_insight}}</p>
    <h2 style="color:#1e40af">Featured Listings This Month</h2>
    <p>{{featured_listings}}</p>
    <div style="text-align:center;margin:32px 0">
      <a href="#" style="display:inline-block;background:#3b82f6;color:#fff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:bold">Browse All Listings</a>
    </div>
    <p>Questions? Reply to this email or call your agent.<br><br>{{agent_name}}</p>
  </div>
  <div style="padding:16px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0">
    Demo Agency · Lagos & Abuja, Nigeria<br>
    <a href="#" style="color:#94a3b8">Unsubscribe</a> · <a href="#" style="color:#94a3b8">View in browser</a>
  </div>
</div>
HTML,
                'body_text'  => "Hi {{first_name}},\n\nDemo Agency Market Update — {{month}} {{year}}\n\n{{market_insight}}\n\nFeatured Listings: {{featured_listings}}\n\n{{agent_name}}",
            ],

            [
                'name'       => 'Open House Invitation',
                'slug'       => 'open-house-invitation',
                'subject'    => "You're invited: Open House at {{property_address}}",
                'category'   => 'marketing',
                'variables'  => ['first_name', 'property_address', 'date', 'start_time', 'end_time', 'features', 'rsvp_url', 'agent_name', 'agent_phone'],
                'body_html'  => <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1e293b">
  <div style="background:#0f172a;padding:32px 24px;text-align:center">
    <p style="color:#f59e0b;margin:0;font-size:14px;text-transform:uppercase;letter-spacing:2px">You're Invited</p>
    <h1 style="color:#fff;margin:8px 0;font-size:28px">Open House</h1>
  </div>
  <div style="padding:32px 24px">
    <p>Dear {{first_name}},</p>
    <p>We warmly invite you to an exclusive open house viewing.</p>
    <div style="border:2px solid #f59e0b;border-radius:12px;padding:20px;margin:24px 0;text-align:center">
      <h2 style="margin:0;color:#1e293b">{{property_address}}</h2>
      <p style="font-size:20px;font-weight:bold;color:#f59e0b;margin:8px 0">{{date}}</p>
      <p style="margin:0;color:#64748b">{{start_time}} – {{end_time}}</p>
    </div>
    <p><strong>Property Highlights:</strong><br>{{features}}</p>
    <div style="text-align:center;margin:32px 0">
      <a href="{{rsvp_url}}" style="display:inline-block;background:#f59e0b;color:#0f172a;padding:14px 40px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:16px">RSVP Now</a>
    </div>
    <p>For enquiries: {{agent_name}} · {{agent_phone}}</p>
  </div>
</div>
HTML,
                'body_text'  => "Dear {{first_name}},\n\nOpen House Invitation!\n{{property_address}}\n{{date}} | {{start_time}} – {{end_time}}\n\nFeatures: {{features}}\n\nRSVP: {{rsvp_url}}\nContact: {{agent_name}} | {{agent_phone}}",
            ],

            // ── SYSTEM CATEGORY ────────────────────────────────────────────────

            [
                'name'       => 'Tenant Portal Access',
                'slug'       => 'tenant-portal-access',
                'subject'    => 'Your Tenant Portal is ready — {{property_address}}',
                'category'   => 'system',
                'variables'  => ['tenant_name', 'property_address', 'portal_url', 'agent_name'],
                'body_html'  => <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1e293b">
  <div style="background:#3b82f6;padding:24px;text-align:center">
    <h2 style="color:#fff;margin:0">Your Tenant Portal</h2>
  </div>
  <div style="padding:24px">
    <p>Dear {{tenant_name}},</p>
    <p>Your online tenant portal for <strong>{{property_address}}</strong> is now active.</p>
    <div style="background:#eff6ff;border:1px solid #93c5fd;border-radius:8px;padding:16px;margin:20px 0">
      <p style="margin:0 0 8px;font-weight:bold">With your portal you can:</p>
      <ul style="margin:0;padding-left:20px;color:#374151">
        <li>View your lease details and payment history</li>
        <li>Submit and track maintenance requests</li>
        <li>Download documents and receipts</li>
      </ul>
    </div>
    <div style="text-align:center;margin:24px 0">
      <a href="{{portal_url}}" style="display:inline-block;background:#3b82f6;color:#fff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:bold">Access Portal</a>
    </div>
    <p>This link is unique to you — do not share it. Contact {{agent_name}} if you have any questions.</p>
    <p>Kind regards,<br>{{agent_name}}<br>Demo Agency</p>
  </div>
</div>
HTML,
                'body_text'  => "Dear {{tenant_name}},\n\nYour tenant portal for {{property_address}} is active.\nAccess: {{portal_url}}\n\nView lease, payments, submit maintenance requests, and download documents.\n\n{{agent_name}}",
            ],

            [
                'name'       => 'Maintenance Request Update',
                'slug'       => 'maintenance-request-update',
                'subject'    => 'Maintenance Update: {{request_title}} — {{status}}',
                'category'   => 'system',
                'variables'  => ['tenant_name', 'request_title', 'status', 'status_message', 'scheduled_date', 'agent_name', 'agent_phone'],
                'body_html'  => <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1e293b">
  <div style="background:#6366f1;padding:24px;text-align:center">
    <h2 style="color:#fff;margin:0">Maintenance Request Update</h2>
  </div>
  <div style="padding:24px">
    <p>Dear {{tenant_name}},</p>
    <p>Your maintenance request <strong>"{{request_title}}"</strong> has been updated.</p>
    <div style="background:#eef2ff;border-left:4px solid #6366f1;padding:12px 16px;margin:20px 0">
      <p style="margin:0;font-weight:bold;color:#4338ca">Status: {{status}}</p>
      <p style="margin:8px 0 0;color:#374151">{{status_message}}</p>
      @if({{scheduled_date}})<p style="margin:4px 0 0;color:#374151">Scheduled: <strong>{{scheduled_date}}</strong></p>@endif
    </div>
    <p>For queries, contact {{agent_name}} on {{agent_phone}}.</p>
    <p>Kind regards,<br>{{agent_name}}<br>Demo Agency Property Management</p>
  </div>
</div>
HTML,
                'body_text'  => "Dear {{tenant_name}},\n\nMaintenance request '{{request_title}}' update:\nStatus: {{status}}\n{{status_message}}\nScheduled: {{scheduled_date}}\n\n{{agent_name}} | {{agent_phone}}",
            ],
        ];

        foreach ($templates as $tpl) {
            EmailTemplate::firstOrCreate(
                ['agency_id' => $agencyId, 'slug' => $tpl['slug']],
                [
                    'name'                => $tpl['name'],
                    'subject'             => $tpl['subject'],
                    'category'            => $tpl['category'],
                    'body_html'           => $tpl['body_html'],
                    'body_text'           => $tpl['body_text'] ?? null,
                    'available_variables' => $tpl['variables'],
                    'is_active'           => true,
                ]
            );
        }
    }

    // ── Email Logs ─────────────────────────────────────────────────────────────

    private function seedEmailLogs(int $agencyId, User $agent, $contacts): void
    {
        $templates = EmailTemplate::where('agency_id', $agencyId)->get()->keyBy('slug');

        $logs = [
            ['template' => 'lead-welcome',         'contact_idx' => 0,  'status' => 'opened',    'days_ago' => 10],
            ['template' => 'lead-welcome',         'contact_idx' => 1,  'status' => 'clicked',   'days_ago' => 8],
            ['template' => 'lead-welcome',         'contact_idx' => 2,  'status' => 'delivered', 'days_ago' => 7],
            ['template' => 'lead-qualification',   'contact_idx' => 0,  'status' => 'opened',    'days_ago' => 9],
            ['template' => 'lead-qualification',   'contact_idx' => 3,  'status' => 'sent',      'days_ago' => 6],
            ['template' => 'new-listing-alert',    'contact_idx' => 1,  'status' => 'opened',    'days_ago' => 5],
            ['template' => 'new-listing-alert',    'contact_idx' => 4,  'status' => 'clicked',   'days_ago' => 4],
            ['template' => 'offer-received-seller','contact_idx' => 5,  'status' => 'opened',    'days_ago' => 3],
            ['template' => 'offer-accepted-buyer', 'contact_idx' => 0,  'status' => 'opened',    'days_ago' => 2],
            ['template' => 'fica-documents-request','contact_idx' => 2, 'status' => 'delivered', 'days_ago' => 6],
            ['template' => 'rent-payment-receipt', 'contact_idx' => 6,  'status' => 'opened',    'days_ago' => 5],
            ['template' => 'rent-payment-receipt', 'contact_idx' => 7,  'status' => 'opened',    'days_ago' => 2],
            ['template' => 'lease-expiry-reminder','contact_idx' => 8,  'status' => 'opened',    'days_ago' => 7],
            ['template' => 'tenant-portal-access', 'contact_idx' => 9,  'status' => 'clicked',   'days_ago' => 4],
            ['template' => 'monthly-newsletter',   'contact_idx' => 0,  'status' => 'clicked',   'days_ago' => 3],
            ['template' => 'monthly-newsletter',   'contact_idx' => 3,  'status' => 'opened',    'days_ago' => 3],
            ['template' => 'monthly-newsletter',   'contact_idx' => 5,  'status' => 'bounced',   'days_ago' => 3],
            ['template' => 'open-house-invitation','contact_idx' => 1,  'status' => 'opened',    'days_ago' => 2],
            ['template' => 'open-house-invitation','contact_idx' => 4,  'status' => 'delivered', 'days_ago' => 2],
            ['template' => 'seller-report-monthly','contact_idx' => 2,  'status' => 'opened',    'days_ago' => 1],
        ];

        foreach ($logs as $log) {
            $contactIdx = $log['contact_idx'];
            if ($contactIdx >= $contacts->count()) {
                continue;
            }
            $contact  = $contacts->values()->get($contactIdx);
            $template = $templates->get($log['template']);

            if (! $template) {
                continue;
            }

            $sentAt    = now()->subDays($log['days_ago'])->setTime(rand(8, 18), rand(0, 59));
            $openedAt  = in_array($log['status'], ['opened', 'clicked']) ? $sentAt->copy()->addMinutes(rand(10, 120)) : null;
            $clickedAt = $log['status'] === 'clicked' ? $openedAt?->copy()->addMinutes(rand(2, 20)) : null;

            $exists = EmailLog::where('agency_id', $agencyId)
                ->where('email_template_id', $template->id)
                ->where('contact_id', $contact->id)
                ->exists();

            if ($exists) {
                continue;
            }

            EmailLog::create([
                'agency_id'           => $agencyId,
                'email_template_id'   => $template->id,
                'contact_id'          => $contact->id,
                'sent_by'             => $agent->id,
                'to_email'            => $contact->email ?? "contact{$contactIdx}@example.com",
                'to_name'             => $contact->full_name,
                'subject'             => str_replace('{{first_name}}', $contact->first_name, $template->subject),
                'status'              => $log['status'],
                'sent_at'             => $sentAt,
                'opened_at'           => $openedAt,
                'clicked_at'          => $clickedAt,
                'provider_message_id' => 'msg_' . \Illuminate\Support\Str::random(24),
            ]);
        }
    }
}
