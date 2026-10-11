<?php

declare(strict_types=1);

namespace Database\Seeders;

use Slendie\Database\Connection;
use Slendie\Database\Seeders\Seeder;

class LeadSeeder extends Seeder
{
    public function run(Connection $db): void
    {
        $now = date('Y-m-d H:i:s');

        $leads = [
            ['Ana Silva', 'ana@example.com', null, 'Quero saber mais.', 'landing', 'new'],
            ['Rui Costa', 'rui@example.com', '+351 910 000 000', null, 'landing', 'contacted'],
            ['Marta Reis', 'marta@example.com', null, 'Podem ligar-me?', 'newsletter', 'new'],
        ];

        $db->table('leads')->insert(array_map(
            fn (array $lead): array => [
                'name' => $lead[0],
                'email' => $lead[1],
                'phone' => $lead[2],
                'message' => $lead[3],
                'source' => $lead[4],
                'status' => $lead[5],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $leads,
        ));
    }
}
