<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\SmsMessage;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\WhatsAppMessage;
use App\Infrastructure\Persistence\Models\WhatsAppTemplate;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MessagingInboxSeeder extends Seeder
{
    public function run(): void
    {
        $agency    = Agency::where('slug', 'demo')->firstOrFail();
        $agent     = User::where('email', 'agent@villacrm.app')->firstOrFail();
        $principal = User::where('email', 'principal@villacrm.app')->firstOrFail();
        $contacts  = Contact::where('agency_id', $agency->id)->get();

        if ($contacts->isEmpty()) {
            return;
        }

        $this->seedWhatsAppTemplates($agency->id);
        $this->seedWhatsAppConversations($agency->id, $agent, $principal, $contacts);
        $this->seedSmsMessages($agency->id, $agent, $principal, $contacts);
    }

    // ── WhatsApp Templates ────────────────────────────────────────────────────

    private function seedWhatsAppTemplates(int $agencyId): void
    {
        $templates = [
            [
                'name'      => 'new_listing_alert',
                'category'  => 'marketing',
                'status'    => 'approved',
                'body'      => "Hello {{buyer_name}}, we have a new {{property_type}} in {{location}} that matches your search criteria.\n\n📍 {{address}}\n💰 ₦{{price}}\n🛏 {{bedrooms}} bed | 🛁 {{bathrooms}} bath\n\nReply YES to schedule a viewing or call {{agent_phone}}.",
                'variables' => ['buyer_name', 'property_type', 'location', 'address', 'price', 'bedrooms', 'bathrooms', 'agent_phone'],
            ],
            [
                'name'      => 'viewing_reminder',
                'category'  => 'utility',
                'status'    => 'approved',
                'body'      => "Hi {{contact_name}}, this is a reminder of your property viewing scheduled for *{{date}}* at *{{time}}*.\n\n📍 {{address}}\n🏠 {{agent_name}} will meet you there.\n\nReply CONFIRM to confirm or CANCEL to reschedule.",
                'variables' => ['contact_name', 'date', 'time', 'address', 'agent_name'],
            ],
            [
                'name'      => 'offer_received',
                'category'  => 'utility',
                'status'    => 'approved',
                'body'      => "Dear {{seller_name}}, an offer of *₦{{amount}}* has been received on your property at {{address}}.\n\nOffer expires: {{expiry_date}}\nConditions: {{conditions_summary}}\n\nPlease contact {{agent_name}} at {{agent_phone}} to discuss.",
                'variables' => ['seller_name', 'amount', 'address', 'expiry_date', 'conditions_summary', 'agent_name', 'agent_phone'],
            ],
            [
                'name'      => 'rent_due_reminder',
                'category'  => 'utility',
                'status'    => 'approved',
                'body'      => "Hi {{tenant_name}}, this is a friendly reminder that your rent of *₦{{amount}}* for {{month}} is due on {{due_date}}.\n\nBank: {{bank_name}}\nAccount: {{account_number}}\nRef: {{lease_reference}}\n\nContact us at {{agent_phone}} if you have any queries.",
                'variables' => ['tenant_name', 'amount', 'month', 'due_date', 'bank_name', 'account_number', 'lease_reference', 'agent_phone'],
            ],
            [
                'name'      => 'lead_follow_up',
                'category'  => 'marketing',
                'status'    => 'approved',
                'body'      => "Hello {{name}}! 👋 I'm {{agent_name}} from Demo Agency. You recently enquired about properties in {{area}}.\n\nI'd love to help you find your perfect {{property_type}}. Are you available for a quick call this week?\n\nReply with a preferred time or call me directly on {{agent_phone}}.",
                'variables' => ['name', 'agent_name', 'area', 'property_type', 'agent_phone'],
            ],
            [
                'name'      => 'document_request',
                'category'  => 'utility',
                'status'    => 'pending_approval',
                'body'      => "Hi {{name}}, to proceed with your application we require the following documents:\n\n{{document_list}}\n\nPlease send via WhatsApp or email to {{agent_email}}.\n\nDeadline: {{deadline}}\n\nThank you — {{agent_name}}.",
                'variables' => ['name', 'document_list', 'agent_email', 'deadline', 'agent_name'],
            ],
        ];

        foreach ($templates as $tpl) {
            WhatsAppTemplate::firstOrCreate(
                ['agency_id' => $agencyId, 'name' => $tpl['name']],
                [
                    'category'  => $tpl['category'],
                    'body'      => $tpl['body'],
                    'variables' => $tpl['variables'],
                    'status'    => $tpl['status'],
                ]
            );
        }
    }

    // ── WhatsApp Conversations ─────────────────────────────────────────────────

    private function seedWhatsAppConversations(int $agencyId, User $agent, User $principal, $contacts): void
    {
        // Build a set of realistic multi-turn conversations
        $conversations = [
            [
                'contact_idx' => 0,
                'messages'    => [
                    ['direction' => 'inbound',  'status' => 'read',      'days_ago' => 5,  'hours' => 10, 'body' => "Hello, I saw your listing on PropertyPro for the apartment in Victoria Island. Is it still available?"],
                    ['direction' => 'outbound', 'status' => 'read',      'days_ago' => 5,  'hours' => 10.5, 'body' => "Good morning! Yes, the Victoria Island apartment is still available. It's a beautiful 3-bedroom with a sea view. Would you like to schedule a viewing?"],
                    ['direction' => 'inbound',  'status' => 'read',      'days_ago' => 5,  'hours' => 11, 'body' => "Yes please! What days are you available this week?"],
                    ['direction' => 'outbound', 'status' => 'read',      'days_ago' => 5,  'hours' => 11.2, 'body' => "We have slots available Thursday at 10am or Friday at 2pm. Which works better for you?"],
                    ['direction' => 'inbound',  'status' => 'read',      'days_ago' => 5,  'hours' => 12, 'body' => "Thursday at 10am works perfectly. What's the address?"],
                    ['direction' => 'outbound', 'status' => 'delivered', 'days_ago' => 5,  'hours' => 12.1, 'body' => "Great! The address is 7 Adeola Odeku Street, Victoria Island. Your agent will be waiting in the lobby. See you Thursday! 🏡"],
                    ['direction' => 'inbound',  'status' => 'read',      'days_ago' => 3,  'hours' => 9, 'body' => "The apartment was amazing! I'd like to make an offer. Can we discuss the process?"],
                    ['direction' => 'outbound', 'status' => 'read',      'days_ago' => 3,  'hours' => 9.5, 'body' => "Wonderful news! I'll send you the offer form now. The asking rent is ₦850,000/month with a 2-month deposit. Let me know if you have questions. 😊"],
                ],
            ],
            [
                'contact_idx' => 1,
                'messages'    => [
                    ['direction' => 'outbound', 'status' => 'delivered', 'days_ago' => 8,  'hours' => 14, 'body' => "Hello! We have just listed a new property in Ikoyi that matches your search criteria. 4-bedroom fully detached, ₦1.2M/month. Interested?"],
                    ['direction' => 'inbound',  'status' => 'read',      'days_ago' => 8,  'hours' => 15, 'body' => "Yes! Send me the details and photos please."],
                    ['direction' => 'outbound', 'status' => 'delivered', 'days_ago' => 8,  'hours' => 15.2, 'body' => "📍 14A Bourdillon Road, Ikoyi\n✅ 4 beds | 4 baths\n✅ 24/7 security & power\n✅ BQ, swimming pool, gym\n💰 ₦1.2M/month\n\nI'll send photos in the next message."],
                    ['direction' => 'inbound',  'status' => 'read',      'days_ago' => 7,  'hours' => 9, 'body' => "The photos look great. What's the lease term and when is it available?"],
                    ['direction' => 'outbound', 'status' => 'read',      'days_ago' => 7,  'hours' => 9.3, 'body' => "Minimum 12-month lease, available immediately. We can arrange a viewing any time this week."],
                    ['direction' => 'inbound',  'status' => 'read',      'days_ago' => 6,  'hours' => 11, 'body' => "I've sent my FICA documents by email. Kindly confirm receipt."],
                    ['direction' => 'outbound', 'status' => 'delivered', 'days_ago' => 6,  'hours' => 11.5, 'body' => "Documents received, thank you! We'll review and get back to you within 24 hours. ✅"],
                ],
            ],
            [
                'contact_idx' => 2,
                'messages'    => [
                    ['direction' => 'inbound',  'status' => 'read',      'days_ago' => 12, 'hours' => 8,  'body' => "Good morning. My name is Tunde. I'm looking to buy a property in Lekki Phase 1 budget around ₦200M. Do you have anything?"],
                    ['direction' => 'outbound', 'status' => 'read',      'days_ago' => 12, 'hours' => 8.5, 'body' => "Good morning Tunde! We have 3 properties in your range in Lekki Phase 1. Let me share the details with you."],
                    ['direction' => 'outbound', 'status' => 'read',      'days_ago' => 12, 'hours' => 8.7, 'body' => "Option 1: 22 Admiralty Way — 5 bed detached, ₦185M\nOption 2: 15 Olu Mowo Close — 4 bed + pool, ₦195M\nOption 3: 8 Idowu Martins Crescent — 4 bed semi-detached, ₦175M\n\nAll with titles in order. Which would you like to view first?"],
                    ['direction' => 'inbound',  'status' => 'read',      'days_ago' => 11, 'hours' => 16, 'body' => "The one on Admiralty Way sounds perfect. I'm a cash buyer, no bond. When can I see it?"],
                    ['direction' => 'outbound', 'status' => 'delivered', 'days_ago' => 11, 'hours' => 16.3, 'body' => "Cash buyer — excellent! We can do tomorrow at 11am or Thursday at 3pm. The house is occupied but tenant will accommodate. Which works?"],
                    ['direction' => 'inbound',  'status' => 'read',      'days_ago' => 11, 'hours' => 17, 'body' => "Tomorrow at 11am is fine. Please send me the exact address."],
                ],
            ],
            [
                'contact_idx' => 3,
                'messages'    => [
                    ['direction' => 'inbound',  'status' => 'read',      'days_ago' => 2,  'hours' => 7,  'body' => "Hi, I submitted a maintenance request last week for the burst pipe in my bathroom. Has anyone attended to it yet?"],
                    ['direction' => 'outbound', 'status' => 'delivered', 'days_ago' => 2,  'hours' => 8,  'body' => "Hi Amina, I apologize for the delay. I've escalated this to our maintenance team. A plumber will be at your property today between 2pm and 5pm. I'll confirm once the job is complete."],
                    ['direction' => 'inbound',  'status' => 'read',      'days_ago' => 2,  'hours' => 18, 'body' => "The plumber came and fixed the pipe, thank you! However there's still some water damage on the ceiling in the bedroom. When can that be assessed?"],
                    ['direction' => 'outbound', 'status' => 'read',      'days_ago' => 2,  'hours' => 18.5, 'body' => "Glad the pipe is sorted! I'll have our contractor assess the ceiling damage on Thursday and we'll get back to you with a repair timeline. It will be covered by the landlord. 👍"],
                    ['direction' => 'inbound',  'status' => 'delivered', 'days_ago' => 1,  'hours' => 9,  'body' => "When is my lease renewal due? I'd like to renew for another year."],
                    ['direction' => 'outbound', 'status' => 'sent',      'days_ago' => 0,  'hours' => 8,  'body' => "Your current lease expires on 30 September 2026. We'd be happy to renew! I'll prepare the renewal offer with the 7.5% escalation and send it to you by end of week. 📋"],
                ],
            ],
            [
                'contact_idx' => 4,
                'messages'    => [
                    ['direction' => 'outbound', 'status' => 'read',      'days_ago' => 3,  'hours' => 13, 'body' => "Good afternoon! We noticed your property at 15 Kings Close, Ikoyi has been on the market for 45 days. We'd love to discuss a revised pricing strategy. Are you available for a quick call?"],
                    ['direction' => 'inbound',  'status' => 'read',      'days_ago' => 3,  'hours' => 14, 'body' => "Yes, I've been a bit concerned about the lack of activity. What do you suggest?"],
                    ['direction' => 'outbound', 'status' => 'delivered', 'days_ago' => 3,  'hours' => 14.5, 'body' => "Based on recent comparable sales in Ikoyi, we recommend reducing the asking price by 8-10% to ₦162M–₦168M. This will attract more serious buyers. Shall I prepare a full CMA report?"],
                    ['direction' => 'inbound',  'status' => 'read',      'days_ago' => 2,  'hours' => 10, 'body' => "Yes, please send the CMA. I'll discuss with my family and revert."],
                    ['direction' => 'outbound', 'status' => 'delivered', 'days_ago' => 2,  'hours' => 10.5, 'body' => "CMA report sent to your email. Take your time and let me know if you have questions. We're here to help! 🙏"],
                ],
            ],
        ];

        foreach ($conversations as $conv) {
            $contactIdx = $conv['contact_idx'];
            if ($contactIdx >= $contacts->count()) {
                continue;
            }
            $contact = $contacts->values()->get($contactIdx);

            foreach ($conv['messages'] as $msg) {
                $sentAt = now()
                    ->subDays($msg['days_ago'])
                    ->setHour((int) $msg['hours'])
                    ->setMinute((int) (($msg['hours'] - floor($msg['hours'])) * 60))
                    ->setSecond(0);

                $exists = WhatsAppMessage::where('contact_id', $contact->id)
                    ->where('body', $msg['body'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                WhatsAppMessage::create([
                    'agency_id'    => $agencyId,
                    'contact_id'   => $contact->id,
                    'to_number'    => $contact->phone ?? '+23480' . rand(10000000, 99999999),
                    'body'         => $msg['body'],
                    'direction'    => $msg['direction'],
                    'status'       => $msg['status'],
                    'sent_at'      => $sentAt,
                    'delivered_at' => in_array($msg['status'], ['delivered', 'read']) ? $sentAt->copy()->addMinutes(2) : null,
                ]);
            }
        }
    }

    // ── SMS Messages ───────────────────────────────────────────────────────────

    private function seedSmsMessages(int $agencyId, User $agent, User $principal, $contacts): void
    {
        $smsScenarios = [
            [
                'contact_idx' => 0,
                'messages'    => [
                    ['direction' => 'outbound', 'status' => 'delivered', 'days_ago' => 6,  'body' => "Hi Test Contact 1, your viewing for 7 Adeola Odeku St is confirmed for Thu 29 May at 10am. Reply STOP to opt out."],
                    ['direction' => 'inbound',  'status' => 'delivered', 'days_ago' => 6,  'body' => "Confirmed, see you Thursday!"],
                    ['direction' => 'outbound', 'status' => 'delivered', 'days_ago' => 3,  'body' => "Congratulations! Your offer on 7 Adeola Odeku has been accepted. We will contact you shortly to proceed."],
                ],
            ],
            [
                'contact_idx' => 2,
                'messages'    => [
                    ['direction' => 'outbound', 'status' => 'delivered', 'days_ago' => 14, 'body' => "Hello Tunde, we have a new 5-bed in Lekki Phase 1 matching your criteria at ₦185M. Interested? Call 0800-VILLACRM or reply YES."],
                    ['direction' => 'inbound',  'status' => 'delivered', 'days_ago' => 14, 'body' => "YES please send details"],
                    ['direction' => 'outbound', 'status' => 'delivered', 'days_ago' => 11, 'body' => "Viewing confirmed: 22 Admiralty Way, Lekki Phase 1 tomorrow 11am. Address pin sent to your WhatsApp."],
                ],
            ],
            [
                'contact_idx' => 5,
                'messages'    => [
                    ['direction' => 'outbound', 'status' => 'delivered', 'days_ago' => 7,  'body' => "Dear Tenant, your rent of ₦1,500,000 for June 2026 is due on 01 Jun. Bank: GTB | Acc: 0123456789 | Ref: LSE-DEMO. Thank you."],
                    ['direction' => 'inbound',  'status' => 'delivered', 'days_ago' => 5,  'body' => "Payment made. Please confirm receipt."],
                    ['direction' => 'outbound', 'status' => 'delivered', 'days_ago' => 5,  'body' => "Payment confirmed ✓ Thank you! Ref PAY-JUNE2026. Receipt will be emailed within 24hrs."],
                ],
            ],
            [
                'contact_idx' => 7,
                'messages'    => [
                    ['direction' => 'outbound', 'status' => 'failed',    'days_ago' => 3,  'body' => "Demo Agency: Your lease inspection is scheduled for 05 Jun 2026 at 10am. Please ensure the property is accessible."],
                ],
            ],
            [
                'contact_idx' => 9,
                'messages'    => [
                    ['direction' => 'outbound', 'status' => 'delivered', 'days_ago' => 1,  'body' => "Hi! Following up on the Maitama property you enquired about. It's still available. Would you like to arrange a viewing this week?"],
                    ['direction' => 'inbound',  'status' => 'delivered', 'days_ago' => 0,  'body' => "Yes, Saturday morning works for me."],
                    ['direction' => 'outbound', 'status' => 'sent',      'days_ago' => 0,  'body' => "Perfect! Saturday 07 Jun at 9am. 45 Aso Drive, Maitama. See you then!"],
                ],
            ],
        ];

        foreach ($smsScenarios as $scenario) {
            $contactIdx = $scenario['contact_idx'];
            if ($contactIdx >= $contacts->count()) {
                continue;
            }
            $contact = $contacts->values()->get($contactIdx);

            foreach ($scenario['messages'] as $msg) {
                $sentAt = now()->subDays($msg['days_ago'])->setTime(rand(8, 18), rand(0, 59));

                $exists = SmsMessage::where('contact_id', $contact->id)
                    ->where('body', $msg['body'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                SmsMessage::create([
                    'agency_id'           => $agencyId,
                    'contact_id'          => $contact->id,
                    'sent_by'             => $msg['direction'] === 'outbound' ? $agent->id : null,
                    'to_number'           => $msg['direction'] === 'outbound'
                        ? ($contact->phone ?? '+23480' . rand(10000000, 99999999))
                        : '+23480VILLACRM01',
                    'from_number'         => $msg['direction'] === 'inbound'
                        ? ($contact->phone ?? '+23480' . rand(10000000, 99999999))
                        : '+23480VILLACRM01',
                    'body'                => $msg['body'],
                    'direction'           => $msg['direction'],
                    'status'              => $msg['status'],
                    'provider'            => 'twilio',
                    'provider_message_id' => 'SM' . strtoupper(\Illuminate\Support\Str::random(32)),
                    'cost'                => $msg['direction'] === 'outbound' ? 0.0075 : null,
                    'sent_at'             => $sentAt,
                    'delivered_at'        => $msg['status'] === 'delivered' ? $sentAt->copy()->addSeconds(rand(3, 30)) : null,
                ]);
            }
        }
    }
}
