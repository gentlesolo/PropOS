<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileTaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $tasks = Task::where('assigned_to', $user->id)
            ->with('contact:id,first_name,last_name')
            ->orderBy('due_at')
            ->get();

        return response()->json($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'contact_id' => 'nullable|exists:contacts,id',
            'due_at'     => 'nullable|date',
            'call_id'    => 'nullable|exists:calls,id',
        ]);

        $user = $request->user();

        $task = Task::create([
            'agency_id'   => $user->agency_id,
            'assigned_to' => $user->id,
            'created_by'  => $user->id,
            'title'       => $request->title,
            'contact_id'  => $request->contact_id,
            'due_at'      => $request->due_at,
            'status'      => 'pending',
            'source'      => 'call_summary',
            'source_id'   => $request->call_id,
        ]);

        return response()->json($task->load('contact:id,first_name,last_name'), 201);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        abort_unless($task->assigned_to === $request->user()->id, 403);

        $request->validate([
            'title'  => 'sometimes|string|max:255',
            'status' => 'sometimes|in:pending,in_progress,completed,snoozed,cancelled',
            'due_at' => 'sometimes|nullable|date',
        ]);

        $task->update($request->only(['title', 'status', 'due_at']));

        if ($request->status === 'completed') {
            $task->update(['completed_at' => now()]);
        }

        return response()->json($task->fresh());
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        abort_unless($task->assigned_to === $request->user()->id, 403);
        $task->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
