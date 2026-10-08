<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberListExportController extends Controller
{
    public function __invoke(Request $request, AuditLogger $audit): StreamedResponse
    {
        $search = trim((string) $request->query('search', ''));
        $roleFilter = trim((string) $request->query('roleFilter', ''));
        $statusFilter = trim((string) $request->query('statusFilter', ''));
        $smallGroupFilter = trim((string) $request->query('smallGroupFilter', ''));
        $tagFilter = trim((string) $request->query('tagFilter', ''));
        $locationFilter = trim((string) $request->query('locationFilter', ''));
        $birthdateFrom = trim((string) $request->query('birthdateFrom', ''));
        $birthdateTo = trim((string) $request->query('birthdateTo', ''));
        $minAge = trim((string) $request->query('minAge', ''));
        $maxAge = trim((string) $request->query('maxAge', ''));
        $requestedSort = (string) $request->query('sortBy', 'created_at');
        $sortBy = in_array($requestedSort, ['created_at', 'name', 'role'], true)
            ? $requestedSort
            : 'created_at';
        $sortDirection = strtolower((string) $request->query('sortDirection', 'desc')) === 'asc' ? 'asc' : 'desc';

        $users = User::query()
            ->with('tags')
            ->visibleTo($request->user(), 'users.export')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('social_media_url', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereHas('tags', fn ($tagQuery) => $tagQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($roleFilter !== '', fn ($query) => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('slug', $roleFilter)))
            ->when($statusFilter !== '', fn ($query) => $query->where('status', $statusFilter))
            ->when($smallGroupFilter !== '', function ($query) use ($smallGroupFilter) {
                $query->whereHas('smallGroups', function ($groupQuery) use ($smallGroupFilter) {
                    $groupQuery->where('small_groups.id', $smallGroupFilter)
                        ->where('small_group_members.status', 'active');
                });
            })
            ->when($tagFilter !== '', fn ($query) => $query->whereHas('tags', fn ($tagQuery) => $tagQuery->whereKey($tagFilter)))
            ->when($locationFilter !== '', function ($query) use ($locationFilter) {
                $query->where(function ($q) use ($locationFilter) {
                    $q->where('address', 'like', "%{$locationFilter}%")
                        ->orWhere('street_address', 'like', "%{$locationFilter}%")
                        ->orWhere('city_code', 'like', "%{$locationFilter}%")
                        ->orWhere('province_code', 'like', "%{$locationFilter}%")
                        ->orWhere('barangay_code', 'like', "%{$locationFilter}%");
                });
            })
            ->when($birthdateFrom !== '', fn ($query) => $query->whereDate('birthdate', '>=', $birthdateFrom))
            ->when($birthdateTo !== '', fn ($query) => $query->whereDate('birthdate', '<=', $birthdateTo))
            ->when($minAge !== '' || $maxAge !== '', function ($query) use ($minAge, $maxAge) {
                $query->whereNotNull('birthdate');

                if ($minAge !== '') {
                    $query->whereRaw($this->ageSqlExpression().' >= ?', [(int) $minAge]);
                }

                if ($maxAge !== '') {
                    $query->whereRaw($this->ageSqlExpression().' <= ?', [(int) $maxAge]);
                }
            })
            ->when(
                $sortBy === 'role',
                fn ($query) => $query->orderBy(
                    Role::query()
                        ->select('roles.name')
                        ->join('role_user', 'role_user.role_id', '=', 'roles.id')
                        ->whereColumn('role_user.user_id', 'users.id')
                        ->orderBy('roles.name')
                        ->limit(1),
                    $sortDirection
                ),
                fn ($query) => $query->orderBy("users.{$sortBy}", $sortDirection)
            )
            ->orderBy('users.id');

        $audit->log('exported', 'members', 'Member list exported', null, [], $request->query());

        return response()->streamDownload(function () use ($users) {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, ['Name', 'Email', 'Phone', 'Social Media Link', 'Tags', 'Birthdate', 'Age', 'Address']);

            foreach ($users->cursor() as $user) {
                $birthdate = $this->formatBirthdateForExport($user);
                $age = $this->formatAgeForExport($user);

                fputcsv($handle, [
                    $user->name,
                    $user->email,
                    $user->phone ?? '',
                    $user->social_media_url ?? '',
                    $user->tags->pluck('name')->join('; '),
                    $birthdate,
                    $age,
                    $user->address ?? '',
                ]);
            }

            fclose($handle);
        }, 'member-list-'.now()->format('YmdHis').'.csv');
    }

    protected function formatBirthdateForExport(User $user): string
    {
        return $this->birthdateForExport($user)?->toDateString() ?? '';
    }

    protected function formatAgeForExport(User $user): string
    {
        return (string) ($this->birthdateForExport($user)?->age ?? '');
    }

    protected function birthdateForExport(User $user): ?Carbon
    {
        $birthdate = $user->getRawOriginal('birthdate') ?? $user->birthdate;

        if ($birthdate === null || $birthdate === '') {
            return null;
        }

        try {
            return $birthdate instanceof CarbonInterface
                ? Carbon::instance($birthdate)
                : Carbon::parse((string) $birthdate);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function ageSqlExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "((CAST(strftime('%Y', 'now') AS INTEGER) - CAST(strftime('%Y', birthdate) AS INTEGER)) - (CASE WHEN strftime('%m-%d', 'now') < strftime('%m-%d', birthdate) THEN 1 ELSE 0 END))";
        }

        if ($driver === 'pgsql') {
            return '(EXTRACT(YEAR FROM AGE(CURRENT_DATE, birthdate)))';
        }

        return '(TIMESTAMPDIFF(YEAR, birthdate, CURDATE()))';
    }
}
