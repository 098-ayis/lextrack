<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SystemSettingService
{
    public const SESSION_TIMEOUT_MINUTES = 'security.session_timeout_minutes';

    public const ALLOWED_EMAIL_DOMAIN = 'security.allowed_email_domain';

    public const LOGIN_RATE_LIMIT_PER_MINUTE = 'security.login_rate_limit_per_minute';

    public const SYSTEM_NOTIFICATIONS_ENABLED = 'notifications.system_enabled';

    public const REMINDER_NOTIFICATIONS_ENABLED = 'notifications.reminders_enabled';

    public const SYSTEM_NAME = 'system.name';

    public const OFFICE_INFORMATION = 'system.office_information';

    public const UPLOAD_SIZE_LIMIT_MB = 'system.upload_size_limit_mb';

    /**
     * @return array<string, string|int|bool>
     */
    public static function defaults(): array
    {
        return [
            self::SESSION_TIMEOUT_MINUTES => 60,
            self::ALLOWED_EMAIL_DOMAIN => 'bicol-u.edu.ph',
            self::LOGIN_RATE_LIMIT_PER_MINUTE => 10,
            self::SYSTEM_NOTIFICATIONS_ENABLED => true,
            self::REMINDER_NOTIFICATIONS_ENABLED => true,
            self::SYSTEM_NAME => 'LexTrack',
            self::OFFICE_INFORMATION => 'Bicol University Legal Affairs Office',
            self::UPLOAD_SIZE_LIMIT_MB => 5,
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $default ??= self::defaults()[$key] ?? null;
        $cacheKey = 'lextrack.system-setting.'.sha1($key);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($key, $default): mixed {
            try {
                return SystemSetting::query()
                    ->where('setting_key', $key)
                    ->value('value') ?? $default;
            } catch (\Throwable) {
                // Keep authentication and middleware usable during deployment
                // before the settings migration has been applied.
                return $default;
            }
        });
    }

    public function getString(string $key, ?string $default = null): string
    {
        return trim((string) $this->get($key, $default));
    }

    public function getInt(string $key, int $default, int $minimum, int $maximum): int
    {
        return max($minimum, min($maximum, (int) $this->get($key, $default)));
    }

    public function getBool(string $key, bool $default): bool
    {
        return filter_var($this->get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    public function sessionTimeoutMinutes(): int
    {
        return $this->getInt(self::SESSION_TIMEOUT_MINUTES, 60, 5, 480);
    }

    public function allowedEmailDomain(): string
    {
        return ltrim($this->getString(self::ALLOWED_EMAIL_DOMAIN, 'bicol-u.edu.ph'), '@');
    }

    public function loginRateLimitPerMinute(): int
    {
        return $this->getInt(self::LOGIN_RATE_LIMIT_PER_MINUTE, 10, 1, 120);
    }

    public function systemNotificationsEnabled(): bool
    {
        return $this->getBool(self::SYSTEM_NOTIFICATIONS_ENABLED, true);
    }

    public static function notificationsEnabled(): bool
    {
        return app(self::class)->systemNotificationsEnabled();
    }

    public function reminderNotificationsEnabled(): bool
    {
        return $this->getBool(self::REMINDER_NOTIFICATIONS_ENABLED, true);
    }

    public function systemName(): string
    {
        return $this->getString(self::SYSTEM_NAME, 'LexTrack') ?: 'LexTrack';
    }

    public function officeInformation(): string
    {
        return $this->getString(self::OFFICE_INFORMATION, 'Bicol University Legal Affairs Office');
    }

    public function uploadSizeLimitMb(): int
    {
        return $this->getInt(self::UPLOAD_SIZE_LIMIT_MB, 5, 1, 100);
    }

    public function uploadSizeLimitKb(): int
    {
        return $this->uploadSizeLimitMb() * 1024;
    }

    /**
     * @param array<string, string|int|bool> $values
     */
    public function saveMany(array $values): void
    {
        foreach ($values as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['setting_key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value],
            );

            Cache::forget('lextrack.system-setting.'.sha1($key));
        }
    }

    /**
     * @return array<string, string|int|bool>
     */
    public function formData(): array
    {
        return [
            'session_timeout_minutes' => $this->sessionTimeoutMinutes(),
            'allowed_email_domain' => $this->allowedEmailDomain(),
            'login_rate_limit_per_minute' => $this->loginRateLimitPerMinute(),
            'system_notifications_enabled' => $this->systemNotificationsEnabled(),
            'reminder_notifications_enabled' => $this->reminderNotificationsEnabled(),
            'system_name' => $this->systemName(),
            'office_information' => $this->officeInformation(),
            'upload_size_limit_mb' => $this->uploadSizeLimitMb(),
        ];
    }
}
