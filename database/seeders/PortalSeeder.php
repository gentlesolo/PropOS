<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\Portal;
use Illuminate\Database\Seeder;

class PortalSeeder extends Seeder
{
    public function run(): void
    {
        $portals = [
            [
                'name' => 'Property24',
                'code' => 'property24',
                'is_active' => true,
                'base_url' => 'https://www.property24.com',
            ],
            [
                'name' => 'PropertyPro',
                'code' => 'propertypro',
                'is_active' => true,
                'base_url' => 'https://www.propertypro.ng',
            ],
            [
                'name' => 'Private Property',
                'code' => 'privateproperty',
                'is_active' => true,
                'base_url' => 'https://www.privateproperty.co.za',
            ],
        ];

        foreach ($portals as $portal) {
            Portal::updateOrCreate(['code' => $portal['code']], $portal);
        }
    }
}
