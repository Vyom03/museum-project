<?php

namespace Database\Seeders;

use App\Models\AboutContent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AboutContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AboutContent::create([
            'title' => 'Vyom Heritage Museum',
            'paragraph_one' => 'Vyom Heritage Museum is a living archive celebrating India’s textile legacies. Our curators travel across craft clusters, collecting heirloom pieces, oral histories, and the techniques that have shaped textile artistry for centuries.',
            'paragraph_two' => 'The museum is nested within a restored haveli in Ahmedabad and houses rotating exhibits of brocade, resist-dyed textiles, and our conservatory lab. Every program is intentionally intimate so guests can experience conservation in action.',
            'paragraph_three' => 'We collaborate with master artisans, conservation scientists, and design schools to keep the handloom economy vibrant. Whether you are a collector, researcher, or traveller, we welcome you to experience the tapestry of Vyom Heritage.',
            'image_url' => 'https://images.unsplash.com/photo-1495435229349-e86db7bfa013?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80',
        ]);
    }
}
