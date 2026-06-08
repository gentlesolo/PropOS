<?php

namespace App\Application\Identity\DTOs;

class RegisterUserData
{
    public function __construct(
        public readonly string $agencyName,
        public readonly string $slug,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly string $password,
        public readonly string $country = 'NG',
        public readonly string $size = '1-5',
        public readonly string $role = 'principal',
        public readonly string $subscriptionPlan = 'solo',
        public readonly string $billingCycle = 'monthly'
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            agencyName: $data['agency_name'],
            slug: $data['slug'],
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            password: $data['password'],
            country: $data['country'] ?? 'NG',
            size: $data['size'] ?? '1-5',
            role: $data['role'] ?? 'principal',
            subscriptionPlan: $data['subscription_plan'] ?? 'solo',
            billingCycle: $data['billing_cycle'] ?? 'monthly'
        );
    }
}
