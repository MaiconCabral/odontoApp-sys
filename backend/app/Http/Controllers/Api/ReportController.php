<?php

namespace App\Http\Controllers\Api;

use App\Domains\Finance\Models\Payment;
use App\Domains\Finance\Queries\ReportQuery;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    /** Longest period of one report. */
    private const MAX_DAYS = 366;

    /** Report of a period, grouped by day, professional or procedure. */
    public function __invoke(Request $request, ReportQuery $reports): JsonResponse
    {
        Gate::authorize('viewReports', Payment::class);

        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'group' => ['required', Rule::in(ReportQuery::GROUPS)],
        ], [
            'to.after_or_equal' => 'A data final precisa ser igual ou depois da inicial.',
        ]);

        $from = CarbonImmutable::parse($data['from']);
        $to = CarbonImmutable::parse($data['to']);

        if ($from->diffInDays($to) >= self::MAX_DAYS) {
            return response()->json([
                'message' => 'O período pode ter até 366 dias.',
                'errors' => ['to' => ['O período pode ter até 366 dias.']],
            ], 422);
        }

        return response()->json($reports->for($from, $to, $data['group']));
    }
}
