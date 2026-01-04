<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RustDaemonClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FileManagerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_access_file_manager_index(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('file-manager.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('FileManager/Index'));
    }

    public function test_user_can_list_files(): void
    {
        $mockFiles = [
            ['name' => 'index.php', 'type' => 'file', 'size' => 1024, 'modified' => time(), 'permissions' => '644'],
            ['name' => 'public', 'type' => 'directory', 'size' => 4096, 'modified' => time(), 'permissions' => '755'],
        ];

        $path = "/home/{$this->user->name}/web";

        $this->partialMock(RustDaemonClient::class, function ($mock) use ($mockFiles, $path) {
            $mock->shouldReceive('call')
                ->with('list_files', ['path' => $path])
                ->once()
                ->andReturn($mockFiles);
        });

        $response = $this->actingAs($this->user)
            ->get(route('file-manager.list', ['path' => '/']));

        $response->assertStatus(200);
        $response->assertJson($mockFiles);
    }

    public function test_user_can_read_file(): void
    {
        $content = "<?php echo 'hello';";
        $path = "/home/{$this->user->name}/web/index.php";

        $this->partialMock(RustDaemonClient::class, function ($mock) use ($content, $path) {
            $mock->shouldReceive('call')
                ->with('read_file', ['path' => $path])
                ->once()
                ->andReturn($content);
        });

        $response = $this->actingAs($this->user)
            ->get(route('file-manager.read', ['path' => $path]));

        $response->assertStatus(200);
        $response->assertJson(['content' => $content]);
    }

    public function test_user_can_write_file(): void
    {
        $path = "/home/{$this->user->name}/web/test.txt";

        $this->partialMock(RustDaemonClient::class, function ($mock) use ($path) {
            $mock->shouldReceive('call')
                ->with('write_file', [
                    'path' => $path,
                    'content' => 'new content',
                ])
                ->once()
                ->andReturn('File written successfully');
        });

        $response = $this->actingAs($this->user)
            ->post(route('file-manager.write'), [
                'path' => $path,
                'content' => 'new content',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'File written successfully']);
    }

    public function test_user_can_delete_file(): void
    {
        $path = "/home/{$this->user->name}/web/test.txt";

        $this->partialMock(RustDaemonClient::class, function ($mock) use ($path) {
            $mock->shouldReceive('call')
                ->with('delete_file', ['path' => $path])
                ->once()
                ->andReturn('Item deleted successfully');
        });

        $response = $this->actingAs($this->user)
            ->delete(route('file-manager.delete'), [
                'path' => $path,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Item deleted successfully']);
    }

    public function test_user_can_create_directory(): void
    {
        $path = "/home/{$this->user->name}/web/new-folder";

        $this->partialMock(RustDaemonClient::class, function ($mock) use ($path) {
            $mock->shouldReceive('call')
                ->with('create_directory', ['path' => $path])
                ->once()
                ->andReturn('Directory created successfully');
        });

        $response = $this->actingAs($this->user)
            ->post(route('file-manager.create-directory'), [
                'path' => $path,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Directory created successfully']);
    }

    public function test_user_can_upload_file(): void
    {
        $file = UploadedFile::fake()->create('test.txt', 100);
        $content = file_get_contents($file->getRealPath());
        $path = "/home/{$this->user->name}/web/test.txt";

        $this->partialMock(RustDaemonClient::class, function ($mock) use ($content, $path) {
            $mock->shouldReceive('call')
                ->with('write_file', [
                    'path' => $path,
                    'content' => $content,
                ])
                ->once()
                ->andReturn('File written successfully');
        });

        $response = $this->actingAs($this->user)
            ->post(route('file-manager.upload'), [
                'path' => "/home/{$this->user->name}/web",
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'File uploaded successfully']);
    }
}
