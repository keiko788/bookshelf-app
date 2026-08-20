<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\ReadingPlanStoreRequest;
use App\Http\Requests\ReadingPlanUpdateRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * 読書計画一覧画面を表示する。
     *
     * @param  Request  $request  ステータスによる絞り込み条件を含むリクエスト
     * @return View 読書計画一覧画面
     */
    public function index(Request $request): View
    {
        $query = auth()->user()
            ->readingPlans()
            ->with('book');

        $currentStatus = $request->input('status');

        if ($request->filled('status')) {
            $query->where('status', $currentStatus);
        }

        $readingPlans = $query
            ->orderBy('target_date')
            ->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 読書計画登録画面を表示する
     *
     * @return View 読書計画登録画面
     */
    public function create(): View
    {
        $books = Book::all();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画を登録する。
     *
     * @param  ReadingPlanStoreRequest  $request  読書計画登録用のリクエスト
     * @return RedirectResponse 読書計画一覧画面へリダイレクト
     */
    public function store(ReadingPlanStoreRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validated();
        $completedStatus = ReadingPlanStatus::Completed->value;

        $exists = $user->readingPlans()
            ->where('book_id', $validated['book_id'])
            ->where('status', '!=', $completedStatus)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors([
                    'book_id' => 'この書籍には未完了の読書計画が既に存在します。',
                ])
                ->withInput();
        }

        $user->readingPlans()->create([
            'book_id' => $validated['book_id'],
            'target_date' => $validated['target_date'],
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を登録しました。');
    }

    /**
     * 読書計画編集画面を表示する。
     *
     * @param  ReadingPlan  $plan  編集する読書計画
     * @return View 読書計画編集画面
     */
    public function edit(ReadingPlan $plan): View
    {
        $this->authorize('update', $plan);

        $plan->load('book');

        $readingPlan = $plan;

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画を更新する。
     *
     * @param  ReadingPlanUpdateRequest  $request  読書計画更新用のリクエスト
     * @param  ReadingPlan  $plan  更新する読書計画
     * @return RedirectResponse 読書計画一覧画面へリダイレクト
     */
    public function update(ReadingPlanUpdateRequest $request, ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('update', $plan);

        $validated = $request->validated();

        $status = ReadingPlanStatus::InProgress;

        $plan->update([
            'target_date' => $validated['target_date'],
            'status' => $status,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    /**
     * 読書計画を削除する。
     *
     * @param  ReadingPlan  $plan  削除する読書計画
     * @return RedirectResponse 読書計画一覧画面へリダイレクト
     */
    public function destroy(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('delete', $plan);

        $plan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }

    /**
     * 読書計画を読了にする。
     *
     * @param  ReadingPlan  $plan  読了にする読書計画
     * @return RedirectResponse 読書計画一覧画面へリダイレクト
     */
    public function complete(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('complete', $plan);

        $plan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を読了にしました。');
    }
}
