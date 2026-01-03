<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Database;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_databases(): void
    {
        $user = User::factory()->create();
        Database::create([
            'user_id' => $user->id,
            'name' => 'test_db',
            'db_user' => 'test_user',
            'db_password' => 'password123',
            'type' => 'mysql',
        ]);

        $response = $this->actingAs($user)->get('/databases');

        $response->assertStatus(200);
        $response->assertSee('test_db');
    }

    public function test_user_can_create_database(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/databases', [
            'name' => 'new_db',
            'db_user' => 'new_user',
            'db_password' => 'password123',
            'type' => 'mysql',
        ]);

        $response->assertRedirect('/databases');
        $this->assertDatabaseHas('databases', [
            'name' => 'new_db',
            'user_id' => $user->id,
            'db_user' => 'new_user',
        ]);
    }

    public function test_user_can_delete_database(): void
    {
        $user = User::factory()->create();
        $database = Database::create([
            'user_id' => $user->id,
            'name' => 'test_db',
            'db_user' => 'test_user',
            'db_password' => 'password123',
            'type' => 'mysql',
        ]);

        $response = $this->actingAs($user)->delete("/databases/{$database->id}");

        $response->assertRedirect('/databases');
        $this->assertDatabaseMissing('databases', [
            'id' => $database->id,
        ]);
    }
}
