<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Faq::query()->updateOrCreate([
            'slug' => 'harbor-desk-shipping-and-returns',
        ], [
            'question' => 'What should I know about the Harbor Desk?',
            'answer' => 'The Harbor Desk is an ergonomic standing desk. It ships in 2–3 business days and includes a 30-day return window.',
        ]);
    }
}
