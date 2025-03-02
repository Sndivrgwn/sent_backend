<?php

namespace Database\Seeders;

use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GroupDefaultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $divisi = ['Programming', 'Networking', 'Multimedia'];

        foreach ($divisi as $name) {
            // Create a new ChatGroup and store the instance
            $chatGroup = ChatGroup::create([
                'name' => $name,
                'created_by' => 1, // ID admin atau user yang membuat grup
                'img' => null, // Path gambar default
            ]);

            // Use the ID of the newly created ChatGroup to create a ChatGroupMember
            ChatGroupMember::create([
                'group_id' => $chatGroup->id, // Use the ID of the created ChatGroup
                'user_id' => 1,
            ]);
        }
    }
}