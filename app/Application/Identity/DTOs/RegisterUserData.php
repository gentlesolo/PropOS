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
        public readonly string $password
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
            password: $data['password']
        );
    }
}
