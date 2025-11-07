<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'name' => 'Brocade Banarasi Wall Panel',
                'summary' => 'Opulent gold-and-indigo Banarasi brocade woven circa 1910 in Varanasi workshops.',
                'description' => '<p>This antique panel is handwoven using real zari and mulberry silk. It was curated from a private collection in Varanasi and restored by Vyom Heritage conservators.</p><ul><li>Material: Silk & zari threads</li><li>Dimensions: 94cm &times; 62cm</li><li>Includes archival provenance dossier</li></ul>',
                'price' => 18500,
                'compare_at_price' => 21500,
                'inventory_count' => 3,
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1526498460520-4c246339dccb?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=900&q=80',
                ],
            ],
            [
                'name' => 'Kutch Mirrorwork Textile Scroll',
                'summary' => 'Hand-embroidered mirrorwork textile featuring tribal motifs from Kutch, Gujarat.',
                'description' => '<p>Each mirrored motif is hand-appliquéd by Rabari artisans. The scroll is mounted on cotton backing for preservation.</p><p>Perfect for display or as a collectors archival study piece.</p>',
                'price' => 14200,
                'compare_at_price' => null,
                'inventory_count' => 5,
                'is_featured' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1503387762-592deb58ef4e?auto=format&fit=crop&w=900&q=80',
                ],
            ],
            [
                'name' => 'Indigo Ajrakh Shawl',
                'summary' => 'Natural indigo Ajrakh shawl block-printed with hand-carved wooden blocks.',
                'description' => '<p>Crafted in Bhuj using slow multi-stage dyeing with indigo, madder and iron. The shawl arrives in a museum archival box.</p>',
                'price' => 9800,
                'compare_at_price' => 11200,
                'inventory_count' => 8,
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1542293787938-4d2226c12e79?ixlib=rb-4.0.3&auto=format&fit=crop&w=900&q=80',
                ],
            ],
            [
                'name' => 'Handwoven Silk Sari (Ajrakh Palette)',
                'summary' => 'Bhuj-inspired silk sari featuring indigo and madder Ajrakh motifs on handloom silk.',
                'description' => '<p>Limited-edition silk sari co-created with Ajrakh artisans. Features hand block printing, natural dyes, and a hand-rolled edge, presented in archival packaging.</p>',
                'price' => 19800,
                'compare_at_price' => 22800,
                'inventory_count' => 6,
                'is_featured' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1514996937319-344454492b37?ixlib=rb-4.0.3&auto=format&fit=crop&w=900&q=80',
                ],
            ],
            [
                'name' => 'Pichwai Lotus Painting',
                'summary' => 'Miniature Pichwai painting on cotton depicting lotus ponds for Shrinathji.',
                'description' => '<p>Painted in Nathdwara temple ateliers with natural pigments. Mounted on archival board and framed under UV glass.</p>',
                'price' => 26500,
                'compare_at_price' => 29800,
                'inventory_count' => 2,
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1526498460520-4c246339dccb?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1495435229349-e86db7bfa013?auto=format&fit=crop&w=900&q=80',
                ],
            ],
            [
                'name' => 'Art Deco Silver Kada Pair',
                'summary' => 'Sterling silver bangles with Art Deco enamel work, restored by Vyom jewellers.',
                'description' => '<p>1930s era sterling bangles sourced from a Mumbai estate. Enamel is retouched using conservation-friendly pigments.</p><p>Ships with velvet archival pouch.</p>',
                'price' => 15800,
                'compare_at_price' => null,
                'inventory_count' => 4,
                'is_featured' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1522312346375-d1a52e2b99b3?auto=format&fit=crop&w=900&q=80',
                ],
            ],
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        ProductImage::truncate();
        Product::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        foreach ($items as $index => $item) {
            $product = Product::create([
                'sku' => 'VY-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'name' => $item['name'],
                'slug' => Str::slug($item['name'] . '-' . Str::random(4)),
                'summary' => $item['summary'],
                'description' => $item['description'],
                'price' => $item['price'],
                'compare_at_price' => $item['compare_at_price'],
                'inventory_count' => $item['inventory_count'],
                'is_featured' => $item['is_featured'],
                'status' => 'active',
            ]);

            $product->images()->createMany(
                collect($item['images'])->map(fn ($url, $position) => [
                    'file_url' => $url,
                    'alt_text' => $item['name'] . ' image ' . ($position + 1),
                    'is_primary' => $position === 0,
                    'sort_order' => $position,
                ])->toArray()
            );
        }
    }
}

