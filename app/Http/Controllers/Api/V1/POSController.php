<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\POSService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class POSController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected POSService $posService
    ) {}

    public function checkout(Request $request, ?int $branchId = null): JsonResponse
    {
        $this->authorize('pos.use');

        // Use branchId from route if provided, otherwise require it in request
        $validationRules = [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0|max:100',
            'items.*.percent' => 'nullable|boolean',
            'items.*.tax_id' => 'nullable|integer|exists:taxes,id',
            'payments' => 'nullable|array',
            'payments.*.method' => 'required_with:payments|in:cash,card,transfer,cheque',
            'payments.*.amount' => 'required_with:payments|numeric|min:0.01',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'notes' => 'nullable|string|max:1000',
        ];

        // If branchId not in route, require it in request body
        if (! $branchId) {
            $validationRules['branch_id'] = 'required|integer|exists:branches,id';
        }

        $request->validate($validationRules);

        // Merge branchId into request data
        $checkoutData = $request->all();
        if ($branchId) {
            $checkoutData['branch_id'] = $branchId;
        }

        try {
            $sale = $this->posService->checkout($checkoutData);

            return response()->json([
                'success' => true,
                'message' => __('Sale completed successfully'),
                'data' => [
                    'id' => $sale->id,
                    'code' => $sale->code,
                    'grand_total' => $sale->grand_total,
                    'paid_total' => $sale->paid_total,
                    'due_total' => $sale->due_total,
                    'status' => $sale->status,
                    'items' => $sale->items->map(fn ($item) => [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product?->name,
                        'qty' => $item->qty,
                        'unit_price' => $item->price,
                        'discount' => $item->discount,
                        'line_total' => $item->total,
                    ]),
                    'payments' => $sale->payments->map(fn ($p) => [
                        'method' => $p->payment_method,
                        'amount' => $p->amount,
                        'reference_no' => $p->reference_no,
                    ]),
                    'created_at' => $sale->created_at?->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function getCurrentSession(Request $request): JsonResponse
    {
        $this->authorize('pos.use');

        $branchId = $request->query('branch_id');
        $userId = auth()->id();

        if (! $branchId || ! $userId) {
            return response()->json([
                'success' => false,
                'message' => __('Branch ID is required'),
                'data' => null,
            ], 400);
        }

        $session = $this->posService->getCurrentSession((int) $branchId, (int) $userId);

        return response()->json([
            'success' => true,
            'data' => $session ? [
                'id' => $session->id,
                'branch_id' => $session->branch_id,
                'user_id' => $session->user_id,
                'opening_cash' => $session->opening_cash,
                'status' => $session->status,
                'opened_at' => $session->opened_at?->toDateTimeString(),
                'total_transactions' => $session->total_transactions,
                'total_sales' => $session->total_sales,
            ] : null,
        ]);
    }

    public function openSession(Request $request): JsonResponse
    {
        $this->authorize('pos.session.manage');

        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'opening_cash' => 'nullable|numeric|min:0',
        ]);

        $userId = auth()->id();
        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => __('Unauthorized'),
            ], 401);
        }

        try {
            $session = $this->posService->openSession(
                (int) $request->input('branch_id'),
                $userId,
                (float) ($request->input('opening_cash') ?? 0)
            );

            return response()->json([
                'success' => true,
                'message' => __('Session opened successfully'),
                'data' => [
                    'id' => $session->id,
                    'branch_id' => $session->branch_id,
                    'user_id' => $session->user_id,
                    'opening_cash' => $session->opening_cash,
                    'status' => $session->status,
                    'opened_at' => $session->opened_at?->toDateTimeString(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function closeSession(Request $request, int $sessionId): JsonResponse
    {
        $this->authorize('pos.session.manage');

        $request->validate([
            'closing_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $session = $this->posService->closeSession(
                $sessionId,
                (float) $request->input('closing_cash'),
                $request->input('notes')
            );

            return response()->json([
                'success' => true,
                'message' => __('Session closed successfully'),
                'data' => [
                    'id' => $session->id,
                    'opening_cash' => $session->opening_cash,
                    'closing_cash' => $session->closing_cash,
                    'expected_cash' => $session->expected_cash,
                    'cash_difference' => $session->cash_difference,
                    'total_transactions' => $session->total_transactions,
                    'total_sales' => $session->total_sales,
                    'payment_summary' => $session->payment_summary,
                    'opened_at' => $session->opened_at?->toDateTimeString(),
                    'closed_at' => $session->closed_at?->toDateTimeString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function getSessionReport(int $sessionId): JsonResponse
    {
        $this->authorize('pos.daily-report.view');

        try {
            $report = $this->posService->getSessionReport($sessionId);

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }
}
