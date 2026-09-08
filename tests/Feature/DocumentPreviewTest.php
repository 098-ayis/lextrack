<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

class DocumentPreviewTest extends TestCase
{
    private function signIn(bool $admin): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill(['user_id' => 1]);
        $user->shouldReceive('isAdmin')->andReturn($admin);
        $this->actingAs($user);
    }

    public function test_admin_can_open_generated_word_preview_inline(): void
    {
        $this->signIn(true);
        $name = bin2hex(random_bytes(16)).'.pdf';
        $directory = storage_path('app/private/temp-previews');
        File::ensureDirectoryExists($directory);
        $path = $directory.'/'.$name;
        file_put_contents($path, "%PDF-1.4\npreview test");

        try {
            $response = $this->get(route('admin.document.temp-preview', ['file' => $name]));
            $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
            $this->assertSame($path, $response->baseResponse->getFile()->getPathname());
        } finally {
            unlink($path);
        }
    }

    public function test_preview_requires_admin_access(): void
    {
        $url = route('admin.document.temp-preview', ['file' => str_repeat('a', 32).'.pdf']);
        $this->get($url)->assertRedirect('/login');
        $this->signIn(false);
        $this->get($url)->assertForbidden();
    }

    public function test_missing_preview_returns_not_found_and_invalid_filename_is_not_served(): void
    {
        $this->signIn(true);
        $this->get(route('admin.document.temp-preview', ['file' => bin2hex(random_bytes(16)).'.pdf']))->assertNotFound();
        $response = $this->get('/admin/document-temp-preview/.env');
        $this->assertNotSame('application/pdf', $response->headers->get('Content-Type'));
    }
}
