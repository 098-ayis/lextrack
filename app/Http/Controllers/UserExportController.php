<?php

namespace App\Http\Controllers;

use App\Models\User;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserExportController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                throw new RuntimeException('Unable to open the export stream.');
            }

            fputcsv($handle, [
                'Full Name',
                'Email',
                'Username',
                'Status',
                'Role',
                'Joined Date',
                'Last Active',
            ]);

            User::query()
                ->orderBy('name')
                ->cursor()
                ->each(function (User $user) use ($handle): void {
                    fputcsv($handle, [
                        $user->name,
                        $user->email,
                        $user->user_id,
                        $user->status,
                        $user->getRoleNames()->join(', '),
                        $user->join_date,
                        $user->last_login,
                    ]);
                });

            fclose($handle);
        }, 'users-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
