<?php

namespace Tests\Feature;

use App\Models\Calendar;
use App\Models\Document;
use App\Models\User;
use App\Notifications\AdminDocumentSubmittedNotification;
use App\Notifications\DocumentDeadlineReminder;
use App\Services\AdminDocumentNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class AdminNotificationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('documents', function (Blueprint $table) {
            $table->increments('document_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status');
            $table->date('deadline')->nullable();
            $table->timestamps();
        });
        Schema::create('calendars', function (Blueprint $table) {
            $table->increments('sched_id');
            $table->unsignedBigInteger('user_id');
            $table->date('date')->nullable();
            $table->time('time')->nullable();
            $table->string('event')->nullable();
            $table->timestamp('reminder_3_days_sent_at')->nullable();
            $table->timestamp('reminder_1_day_sent_at')->nullable();
            $table->timestamp('reminder_10_minutes_sent_at')->nullable();
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    private function user(string $role): User
    {
        $user = User::create(['name' => $role, 'email' => uniqid().'@example.test']);
        $roleId = DB::table('roles')->insertGetId(['name' => $role, 'guard_name' => 'web']);
        DB::table('model_has_roles')->insert(['role_id' => $roleId, 'model_type' => User::class, 'model_id' => $user->id]);

        return $user;
    }

    public function test_submissions_reach_all_admins_despite_mail_failure_and_group_unread_alerts(): void
    {
        $admins = collect(['Admin', 'Super Admin', 'super_admin'])->map(fn ($role) => $this->user($role));
        $client = $this->user('Client');
        $mail = Mockery::mock(MailChannel::class);
        $mail->shouldReceive('send')->times(6)->andThrow(new TransportException('SMTP unavailable'));
        $this->app->instance(MailChannel::class, $mail);
        $service = app(AdminDocumentNotificationService::class);
        $first = Document::withoutEvents(fn () => Document::create(['user_id' => $client->id, 'status' => 'pending']));
        $service->notifyDocumentSubmitted($first);
        $second = Document::withoutEvents(fn () => Document::create(['user_id' => $client->id, 'status' => 'pending']));
        $service->notifyDocumentSubmitted($second);
        foreach ($admins as $admin) {
            $this->assertSame(1, $admin->notifications()->count());
            $alert = $admin->notifications()->first();
            $this->assertSame(AdminDocumentSubmittedNotification::class, $alert->type);
            $this->assertSame(2, $alert->data['document_count']);
            $this->assertStringContainsString('document='.$second->document_id, $alert->data['redirect_url']);
            $alert->markAsRead();
        }
        $service->notifyDocumentSubmitted($second);
        foreach ($admins as $admin) {
            $this->assertSame(2, $admin->notifications()->count());
            $this->assertSame(1, $admin->unreadNotifications()->count());
        }
        $this->assertSame(0, $client->notifications()->count());
    }

    public function test_calendar_reminders_survive_mail_failure_without_repeating(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 8)->startOfDay());
        $admin = $this->user('Admin');
        $mail = Mockery::mock(MailChannel::class);
        $mail->shouldReceive('send')->times(3)->andThrow(new TransportException('SMTP unavailable'));
        $this->app->instance(MailChannel::class, $mail);
        foreach ([4320, 1440, 10] as $minutes) {
            $scheduled = now()->addMinutes($minutes);
            Calendar::create(['user_id' => $admin->id, 'event' => 'Test event', 'date' => $scheduled->toDateString(), 'time' => $scheduled->format('H:i:s')]);
        }
        $this->artisan('calendar:send-reminders')->assertSuccessful();
        $this->artisan('calendar:send-reminders')->assertSuccessful();
        $this->assertSame(3, $admin->notifications()->count());
        foreach (['3_days', '1_day', '10_minutes'] as $type) {
            $this->assertSame(1, Calendar::whereNotNull('reminder_'.$type.'_sent_at')->count());
        }
    }

    public function test_scheduler_sends_due_reminders_once_and_ignores_other_documents(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 8)->startOfDay());
        $admin = $this->user('Admin');
        $mail = Mockery::mock(MailChannel::class);
        $mail->shouldReceive('send')->times(3)->andThrow(new TransportException('SMTP unavailable'));
        $this->app->instance(MailChannel::class, $mail);
        foreach ([0, 1, 3, 2, -1] as $days) {
            Document::withoutEvents(fn () => Document::create(['user_id' => $admin->id, 'status' => 'pending', 'deadline' => today()->addDays($days)]));
        }
        foreach (['completed', 'rejected', 'archived'] as $status) {
            Document::withoutEvents(fn () => Document::create(['user_id' => $admin->id, 'status' => $status, 'deadline' => today()]));
        }
        $this->artisan('calendar:send-reminders')->assertSuccessful();
        $this->artisan('calendar:send-reminders')->assertSuccessful();
        $alerts = $admin->notifications()->where('type', DocumentDeadlineReminder::class)->get();
        $this->assertCount(3, $alerts);
        $this->assertEqualsCanonicalizing(['deadline_day', '1_day', '3_days'], $alerts->pluck('data.reminder_type')->all());
    }
}
