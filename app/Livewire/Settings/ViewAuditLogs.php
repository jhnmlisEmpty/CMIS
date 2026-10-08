<?php

namespace App\Livewire\Settings;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
#[Title('Audit Logs | Settings')]
class ViewAuditLogs extends Component
{
    use WithPagination;

    public string $search = '';
    public string $action = '';
    public string $module = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public ?int $selectedLogId = null;

    public function updated($property): void
    {
        if (in_array($property, ['search', 'action', 'module', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    private function query(): Builder
    {
        return AuditLog::query()->with('actor')
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query->where('description', 'like', "%{$this->search}%")->orWhereHas('actor', fn ($actor) => $actor->where('name', 'like', "%{$this->search}%"))))
            ->when($this->action, fn ($query) => $query->where('action', $this->action))
            ->when($this->module, fn ($query) => $query->where('module', $this->module))
            ->when($this->dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('created_at', '<=', $this->dateTo));
    }

    public function export(AuditLogger $audit): StreamedResponse
    {
        Gate::authorize('access-control.manage');
        $logs = $this->query()->latest()->get();
        $audit->log('exported', 'audit_logs', 'Filtered audit log exported', null, [], ['action' => $this->action, 'module' => $this->module, 'date_from' => $this->dateFrom, 'date_to' => $this->dateTo]);

        return response()->streamDownload(function () use ($logs): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Date', 'Actor', 'Action', 'Module', 'Description', 'IP address']);
            foreach ($logs as $log) {
                fputcsv($handle, [$log->created_at->toIso8601String(), $log->actor?->name ?? 'System', $log->action, $log->module, $log->description, $log->ip_address]);
            }
            fclose($handle);
        }, 'audit-log-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        Gate::authorize('access-control.manage');
        return view('livewire.settings.view-audit-logs', [
            'logs' => $this->query()->latest()->paginate(20),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
            'modules' => AuditLog::query()->distinct()->orderBy('module')->pluck('module'),
            'selectedLog' => $this->selectedLogId ? AuditLog::with('actor')->find($this->selectedLogId) : null,
        ]);
    }
}
