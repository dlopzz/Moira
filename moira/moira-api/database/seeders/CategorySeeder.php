<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('TRUNCATE TABLE categories RESTART IDENTITY CASCADE');

        $tree = [
            'name' => 'Moira',
            'children' => [
                ['name' => 'Ropa', 'children' => [
                    ['name' => 'Mujer', 'children' => [
                        ['name' => 'Remeras'],
                        ['name' => 'Vestidos'],
                        ['name' => 'Pantalones'],
                        ['name' => 'Camperas'],
                    ]],
                    ['name' => 'Hombre', 'children' => [
                        ['name' => 'Remeras'],
                        ['name' => 'Pantalones'],
                        ['name' => 'Camperas'],
                        ['name' => 'Ropa Interior'],
                    ]],
                    ['name' => 'Niños', 'children' => [
                        ['name' => 'Bebés (0-2 años)'],
                        ['name' => 'Niñas (3-12 años)'],
                        ['name' => 'Niños (3-12 años)'],
                    ]],
                ]],
                ['name' => 'Calzado', 'children' => [
                    ['name' => 'Zapatillas'],
                    ['name' => 'Zapatos', 'children' => [
                        ['name' => 'Formales'],
                        ['name' => 'Casuales'],
                    ]],
                    ['name' => 'Botas'],
                    ['name' => 'Sandalias'],
                ]],
                ['name' => 'Accesorios', 'children' => [
                    ['name' => 'Carteras y Bolsos'],
                    ['name' => 'Cinturones'],
                    ['name' => 'Sombreros y Gorros'],
                    ['name' => 'Bijouterie', 'children' => [
                        ['name' => 'Collares'],
                        ['name' => 'Aros'],
                        ['name' => 'Pulseras'],
                    ]],
                ]],
                ['name' => 'Deportes', 'children' => [
                    ['name' => 'Indumentaria Deportiva'],
                    ['name' => 'Calzado Deportivo'],
                    ['name' => 'Equipamiento'],
                ]],
            ],
        ];

        $this->createTree([$tree]);
    }

    private function createTree(array $items, ?int $parentId = null, int $sort = 0): void
    {
        foreach ($items as $i => $item) {
            $category = Category::create([
                'name'       => $item['name'],
                'slug'       => $this->uniqueSlug($item['name'], $parentId),
                'parent_id'  => $parentId,
                'sort_order' => $sort + $i,
                'is_active'  => true,
            ]);

            if (!empty($item['children'])) {
                $this->createTree($item['children'], $category->id);
            }
        }
    }

    private function uniqueSlug(string $name, ?int $parentId): string
    {
        $base = Str::slug($name);
        $slug = $parentId ? "{$base}-{$parentId}" : $base;
        $count = Category::where('slug', 'like', "{$slug}%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }
}
