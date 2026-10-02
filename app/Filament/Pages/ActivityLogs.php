<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\RoleSecurity;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use UnitEnum;

class ActivityLogs extends Page
{
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'ADMINISTRATION';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Activity Logs';

    protected static ?string $title = 'Activity Logs';

    protected string $view = 'filament.pages.activity-logs';

    public string $search = '';

    public string $categoryFilter = '';

    public string $actionFilter = '';

    public string $modifiedByFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public ?int $selectedLogId = null;

    private const array CATEGORIES = [
        'Authentication',
        'Users',
        'Roles / Permissions',
        'Documents',
        'Requests',
        'Messages',
        'Reports',
        'Calendar',
        'Cabinet',
        'Recycle Bin',
        'System Settings',
    ];

    /**
     * Keep audit presentation separate from the stored action text. This
     * allows the Super Admin view to show what happened without displaying
     * confidential particulars, note text, filenames, or document contents.
     *
     * @var array<string, array{category: string, action: string, description: string}>
     */
    private const array ACTION_DEFINITIONS = [
        'Document created' => [
            'category' => 'Documents',
            'action' => 'Create',
            'description' => 'Document submitted',
        ],
        'Document submitted' => [
            'category' => 'Documents',
            'action' => 'Create',
            'description' => 'Document submitted',
        ],
        'Document accepted' => [
            'category' => 'Documents',
            'action' => 'Perform',
            'description' => 'Document accepted',
        ],
        'Document rejected' => [
            'category' => 'Documents',
            'action' => 'Perform',
            'description' => 'Document rejected',
        ],
        'Document returned' => [
            'category' => 'Documents',
            'action' => 'Perform',
            'description' => 'Document returned',
        ],
        'Document completed' => [
            'category' => 'Documents',
            'action' => 'Perform',
            'description' => 'Document completed',
        ],
        'Document moved to outgoing' => [
            'category' => 'Documents',
            'action' => 'Perform',
            'description' => 'Moved to outgoing',
        ],
        'Document details updated' => [
            'category' => 'Documents',
            'action' => 'Update',
            'description' => 'Document details updated',
        ],
        'Document updated' => [
            'category' => 'Documents',
            'action' => 'Update',
            'description' => 'Document details updated',
        ],
        'Document downloaded' => [
            'category' => 'Documents',
            'action' => 'Perform',
            'description' => 'Document downloaded',
        ],
        'Revision uploaded' => [
            'category' => 'Documents',
            'action' => 'Create',
            'description' => 'Revision uploaded',
        ],
        'Revised document accepted' => [
            'category' => 'Documents',
            'action' => 'Perform',
            'description' => 'Revised document accepted',
        ],
        'Revised document rejected' => [
            'category' => 'Documents',
            'action' => 'Perform',
            'description' => 'Revised document rejected',
        ],
        'Revision deleted' => [
            'category' => 'Documents',
            'action' => 'Delete',
            'description' => 'Revision deleted',
        ],
        'Note added' => [
            'category' => 'Documents',
            'action' => 'Create',
            'description' => 'Note added',
        ],
        'Note updated' => [
            'category' => 'Documents',
            'action' => 'Update',
            'description' => 'Note updated',
        ],
        'Note deleted' => [
            'category' => 'Documents',
            'action' => 'Delete',
            'description' => 'Note deleted',
        ],
        'Document archived' => [
            'category' => 'Recycle Bin',
            'action' => 'Perform',
            'description' => 'Moved to Recycle Bin',
        ],
        'Document returned from archive' => [
            'category' => 'Recycle Bin',
            'action' => 'Perform',
            'description' => 'Restored from Recycle Bin',
        ],
        'Message opened' => [
            'category' => 'Messages',
            'action' => 'Perform',
            'description' => 'Conversation opened',
        ],
        'Action Taken' => [
            'category' => 'Documents',
            'action' => 'Perform',
            'description' => 'Action taken',
        ],
    ];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(RoleSecurity::SUPER_ADMIN) ?? false;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function updated(string $property): void
    {
        if (in_array($property, [
            'search',
            'categoryFilter',
            'actionFilter',
            'modifiedByFilter',
            'dateFrom',
            'dateTo',
        ], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'categoryFilter',
            'actionFilter',
            'modifiedByFilter',
            'dateFrom',
            'dateTo',
        ]);
        $this->resetValidation();
        $this->resetPage();
    }

    public function openDetails(int $logId): void
    {
        abort_unless(static::canAccess(), 403);

        ActivityLog::query()->findOrFail($logId);
        $this->selectedLogId = $logId;
    }

    public function closeDetails(): void
    {
        $this->selectedLogId = null;
    }

    /**
     * @return array{category: string, action: string, action_class: string, description: string, modified_by: string, modified_email: ?string, modified_photo: ?string, subject: string}
     */
    public function presentLog(ActivityLog $log): array
    {
        $classification = self::classifyAction((string) $log->action_type);

        return [
            ...$classification,
            'action_class' => match ($classification['action']) {
                'Create' => 'activity-logs-action-create',
                'Update' => 'activity-logs-action-update',
                'Delete' => 'activity-logs-action-delete',
                default => 'activity-logs-action-perform',
            },
            'modified_by' => $log->user?->historical_display_name ?? 'Unknown User',
            'modified_email' => $log->user?->email,
            'modified_photo' => $log->user?->getProfilePhotoUrl(),
            'subject' => filled($log->subject_type) && $log->subject_id !== null
                ? ltrim((string) $log->subject_type, '\\').' #'.$log->subject_id
                : ($classification['category'] === 'Messages' && $log->document?->conversation?->getKey()
                    ? 'App\\Models\\Conversation #'.$log->document->conversation->getKey()
                    : ($log->document_id !== null
                        ? 'App\\Models\\Document #'.$log->document_id
                        : 'System activity')),
        ];
    }

    /**
     * @return array{category: string, action: string, description: string}
     */
    private static function classifyAction(string $actionType): array
    {
        if (isset(self::ACTION_DEFINITIONS[$actionType])) {
            return self::ACTION_DEFINITIONS[$actionType];
        }

        $category = match (true) {
            Str::startsWith($actionType, ['User logged in', 'User logged out', 'Authentication ', 'Login ', 'Logout ']) => 'Authentication',
            Str::startsWith($actionType, ['User ', 'Account ']) => 'Users',
            Str::startsWith($actionType, ['Role ', 'Permission ']) => 'Roles / Permissions',
            Str::startsWith($actionType, ['Request ']) => 'Requests',
            Str::startsWith($actionType, ['Message ', 'Conversation ']) => 'Messages',
            Str::startsWith($actionType, ['Report ']) => 'Reports',
            Str::startsWith($actionType, ['Calendar ', 'Event ']) => 'Calendar',
            Str::startsWith($actionType, ['Cabinet ']) => 'Cabinet',
            Str::startsWith($actionType, ['Setting ', 'Configuration ']) => 'System Settings',
            default => 'Documents',
        };

        $action = match (true) {
            preg_match('/\b(?:created|submitted|added|uploaded)\b/i', $actionType) === 1 => 'Create',
            preg_match('/\bdeleted\b/i', $actionType) === 1 => 'Delete',
            preg_match('/\b(?:updated|changed|edited)\b/i', $actionType) === 1 => 'Update',
            default => 'Perform',
        };

        $description = trim((string) preg_replace('/^(?:User|Account|Role|Permission|Request|Message|Conversation|Calendar|Event|Cabinet|Authentication|Login|Logout|Setting|Configuration|Document|Revision|Note)\s+/i', '', $actionType));

        return [
            'category' => $category,
            'action' => $action,
            'description' => $description !== '' ? Str::headline($description) : 'Activity recorded',
        ];
    }

    public function export(): mixed
    {
        abort_unless(static::canAccess(), 403);

        $logs = $this->activityQuery($this->availableActionTypes())
            ->with(['user', 'document.conversation'])
            ->get();
        $rows = $logs->map(function (ActivityLog $log): array {
            $presentation = $this->presentLog($log);

            return [
                $presentation['category'],
                $presentation['action'],
                $presentation['description'],
                $presentation['modified_by'],
                $log->created_at?->format('Y-m-d H:i:s') ?? '',
                $presentation['subject'],
            ];
        })->all();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Category', 'Action', 'Description', 'Modified By', 'Date of Change', 'Subject']);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'activity-logs-'.now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $actionTypes = $this->availableActionTypes();
        $actionOptions = collect($actionTypes)
            ->map(fn (string $action): string => self::classifyAction($action)['action'])
            ->unique()
            ->sort()
            ->values();

        $query = $this->activityQuery($actionTypes);
        $selectedLog = $this->selectedLogId
            ? ActivityLog::query()->with(['user', 'document.conversation'])->find($this->selectedLogId)
            : null;

        return [
            'logs' => $query->paginate(15),
            'selectedLog' => $selectedLog,
            'categories' => self::CATEGORIES,
            'actions' => $actionOptions,
            'modifiedUsers' => User::withTrashed()
                ->whereIn('id', ActivityLog::query()->select('user_id')->distinct())
                ->orderBy('name')
                ->get(['id', 'name', 'deleted_at']),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function availableActionTypes(): array
    {
        return ActivityLog::query()
            ->select('action_type')
            ->distinct()
            ->orderBy('action_type')
            ->pluck('action_type')
            ->filter(fn (mixed $action): bool => filled($action))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $actionTypes
     */
    private function activityQuery(array $actionTypes): Builder
    {
        $query = ActivityLog::query()
            ->with(['user', 'document.conversation'])
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $term = '%'.trim($this->search).'%';

                $query->where(function (Builder $query) use ($term): void {
                    $query
                        ->where('action_type', 'like', $term)
                        ->orWhereHas('user', function (Builder $query) use ($term): void {
                            $query
                                ->where('name', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        });
                });
            })
            ->when($this->modifiedByFilter !== '', fn (Builder $query): Builder => $query->where('user_id', (int) $this->modifiedByFilter))
            ->when($this->dateFrom !== '', fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $this->dateTo));

        if ($this->categoryFilter !== '' || $this->actionFilter !== '') {
            $matchingTypes = collect($actionTypes)
                ->filter(function (string $actionType): bool {
                    $classification = self::classifyAction($actionType);

                    return ($this->categoryFilter === '' || $classification['category'] === $this->categoryFilter)
                        && ($this->actionFilter === '' || $classification['action'] === $this->actionFilter);
                })
                ->values()
                ->all();

            if ($matchingTypes === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('action_type', $matchingTypes);
            }
        }

        return $query
            ->latest('created_at')
            ->latest('log_id');
    }
}
