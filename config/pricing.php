<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Subscription Tiers
    |--------------------------------------------------------------------------
    */

    'plans' => [
        'solo' => [
            'name' => 'Solo',
            'job' => 'Independent agents',
            'price_monthly' => 799,
            'price_annual' => 7990,
            'features' => [
                'max_agents' => 1,
                'max_listings' => 15,
                'max_portals' => 1,
                'marketing_channels' => ['email'],
                'ai_brief' => 'basic', // 1 brief/day
            ],
            'ai_credits_monthly' => 200,
            'additional_agent_price' => null, // Not allowed
        ],

        'agency_pro' => [
            'name' => 'Agency Pro',
            'job' => 'Growing agencies',
            'price_monthly' => 2999,
            'price_annual' => 29990,
            'features' => [
                'max_agents' => 5,
                'max_listings' => -1, // Unlimited
                'max_portals' => 2,
                'marketing_channels' => ['email', 'sms', 'whatsapp', 'facebook', 'instagram', 'linkedin', 'portal_ads'],
                'ai_brief' => 'full', // Full brief + nudges
            ],
            'ai_credits_monthly' => 2000,
            'additional_agent_price' => 499, // R499/agent beyond 5
        ],

        'enterprise' => [
            'name' => 'Enterprise',
            'job' => 'Franchises & multi-branch',
            'price_monthly' => 'custom',
            'price_annual' => 'custom',
            'features' => [
                'max_agents' => -1, // Unlimited
                'max_listings' => -1, // Unlimited
                'max_portals' => -1, // Unlimited
                'marketing_channels' => ['all'],
                'ai_brief' => 'full',
            ],
            'ai_credits_monthly' => -1, // Custom
            'additional_agent_price' => 'negotiated',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Credit Costs
    |--------------------------------------------------------------------------
    */

    'credit_costs' => [
        'listing_description' => 5,
        'campaign_generation' => 20,
        'lead_scoring_batch' => 10,
        'call_transcription' => 15,
        'daily_brief' => 2,
        'buyer_match' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Credit Top-Up Packs
    |--------------------------------------------------------------------------
    */

    'top_ups' => [
        'starter' => [
            'name' => 'Starter Top-up',
            'credits' => 500,
            'price' => 199,
        ],
        'pro' => [
            'name' => 'Pro Top-up',
            'credits' => 2000,
            'price' => 599,
        ],
        'bulk' => [
            'name' => 'Bulk Top-up',
            'credits' => 10000,
            'price' => 2499,
        ],
    ],
];
