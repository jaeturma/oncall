<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FinanceReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceReportController extends Controller
{
    public function __construct(private readonly FinanceReportService $reports) {}

    public function index(): View
    {
        Gate::authorize('view-finance-reports');

        return view('admin.finance.index', [
            'reconciliation' => $this->reports->reconciliation(),
            'withdrawalPipeline' => $this->reports->withdrawalPipeline(),
            'jobPayments' => $this->reports->jobPaymentSummary(),
            'commissions' => $this->reports->commissionSummary(),
            'topSponsors' => $this->reports->topSponsors(),
            'recentMovement' => $this->reports->ledgerMovement(now()->subDays(14), now()),
        ]);
    }

    public function ledger(Request $request): View
    {
        Gate::authorize('view-finance-reports');
        [$from, $to] = $this->range($request);

        return view('admin.finance.ledger', [
            'from' => $from,
            'to' => $to,
            'movement' => $this->reports->ledgerMovement($from, $to),
        ]);
    }

    public function userStatement(User $user): View
    {
        Gate::authorize('view-finance-reports');

        return view('admin.finance.statement', [
            'statementUser' => $user,
            'statement' => $this->reports->userStatement($user),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('view-finance-reports');
        [$from, $to] = $this->range($request);
        $rows = $this->reports->ledgerMovement($from, $to);
        $filename = 'oncall-ledger-'.$from->toDateString().'-to-'.$to->toDateString().'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Date', 'Type', 'Entries', 'Total (PHP)']);
            foreach ($rows as $row) {
                fputcsv($handle, [$row->day, $row->type, $row->entries, $row->total]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $to = $request->date('to') ? Carbon::parse($request->date('to')) : now();
        $from = $request->date('from') ? Carbon::parse($request->date('from')) : $to->copy()->subDays(30);

        return [$from->min($to), $to->max($from)];
    }
}
