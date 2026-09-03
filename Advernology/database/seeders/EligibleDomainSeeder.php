<?php

namespace Database\Seeders;

use App\Models\EligibleDomain;
use Illuminate\Database\Seeder;

class EligibleDomainSeeder extends Seeder
{
    public function run(): void
    {
        // Known Lumos / former Lumos customer domains
        $domains = [
            ['domain' => 'lumos.com',      'notes' => 'Primary Lumos domain'],
            ['domain' => 'nfcom.com',      'notes' => 'Former Lumos / NF Communications'],
            ['domain' => 'ntelos.net',     'notes' => 'Former nTelos customers'],
            ['domain' => 'cfw.com',        'notes' => 'CFW / Lumos customers'],
            ['domain' => 'lumosnet.com',   'notes' => 'Lumos Networks'],
            ['domain' => 'rvec.net',       'notes' => 'Rappahannock / RVE customers'],
            ['domain' => 'skyline.net',    'notes' => 'Skyline / Lumos area'],
            ['domain' => 'shentel.net',    'notes' => 'Shentel area customers'],
        ];

        foreach ($domains as $domain) {
            EligibleDomain::firstOrCreate(['domain' => $domain['domain']], array_merge($domain, ['active' => true]));
        }
    }
}
