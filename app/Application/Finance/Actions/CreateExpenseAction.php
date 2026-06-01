<?php

namespace App\Application\Finance\Actions;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\Expense;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\UploadedFile;

class CreateExpenseAction
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function execute(array $data, ?UploadedFile $receipt = null): Expense
    {
        $agencyId = $data['agency_id'];

        if ($receipt) {
            $path = $receipt->store("expenses/{$agencyId}", 'local');
            $data['receipt_path'] = $path;
        }

        $data['period_month'] ??= (int) now()->format('m');
        $data['period_year']  ??= (int) now()->format('Y');

        $expense = Expense::create($data);

        // Notify managers that a new expense needs approval
        $managers = User::where('agency_id', $agencyId)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['principal', 'manager']))
            ->pluck('id');

        foreach ($managers as $managerId) {
            $this->notifications->notifyUser(
                $managerId,
                'expense_created',
                'New Expense Submitted',
                "Expense {$expense->reference} — R " . number_format((float) $expense->amount, 2) . " from {$expense->vendor_name} requires approval.",
                '/finance/expenses',
                'info',
            );
        }

        return $expense;
    }
}
