<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        
        $liam = User::create([
            'name' => 'Liam',
            'email' => 'liam@recolte.ca',
            'password' => Hash::make(env('LIAM_PASS')),
        ]);
        
        $tags = ['financements','fun','ag urbaine','fermes','distribution'];
        foreach ($tags as $tag) {
            Tag::create([
                'name' => $tag,
            ]);
        }
    }
}
