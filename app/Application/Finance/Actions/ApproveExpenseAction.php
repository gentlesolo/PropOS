<?php

namespace App\Application\Finance\Actions;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\Expense;
use App\Infrastructure\Persistence\Models\User;

class ApproveExpenseAction
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function execute(Expense $expense, User $approver, bool $approved = true): Expense
    {
        $status = $approved ? 'approved' : 'rejected';

        $expense->update([
            'status'      => $status,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $action = $approved ? 'approved' : 'rejected';

        $this->notifications->notifyUser(
            $approver->id,
            'expense_' . $action,
            'Expense ' . ucfirst($action),
            "Expense {$expense->reference} has been {$action} by {$approver->name}.",
            '/finance/expenses',
            $approved ? 'success' : 'warning',
        );

        return $expense->fresh();
    }
}
