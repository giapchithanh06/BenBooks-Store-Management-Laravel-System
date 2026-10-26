<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeDoc;
use App\Services\ChatbotService;
use Illuminate\Http\Request;

/** Kho tri thức cho RAG (chính sách, hướng dẫn, FAQ) + khung thử truy xuất */
class KnowledgeController extends Controller
{
    public function index(Request $request, ChatbotService $bot)
    {
        $test = null;
        if ($q = $request->input('q')) {
            $test = ['q' => $q, 'hits' => $bot->retrieve($q, 5), 'answer' => $bot->reply($q)];
        }
        return view('admin.ai.knowledge.index', ['docs' => KnowledgeDoc::orderBy('category')->get(), 'test' => $test]);
    }

    public function create()
    {
        return view('admin.ai.knowledge.form', ['doc' => new KnowledgeDoc(['is_active' => true, 'category' => 'policy'])]);
    }

    public function edit(KnowledgeDoc $knowledge)
    {
        return view('admin.ai.knowledge.form', ['doc' => $knowledge]);
    }

    protected function data(Request $request)
    {
        return $request->validate([
            'title' => 'required|string|max:200',
            'category' => 'required|in:' . implode(',', array_keys(KnowledgeDoc::CATEGORIES)),
            'content' => 'required|string',
        ], [], ['title' => 'tiêu đề', 'content' => 'nội dung']) + ['is_active' => $request->boolean('is_active')];
    }

    public function store(Request $request)
    {
        KnowledgeDoc::create($this->data($request));
        return redirect()->route('admin.ai.knowledge.index')->with('success', 'Đã thêm tài liệu.');
    }

    public function update(Request $request, KnowledgeDoc $knowledge)
    {
        $knowledge->update($this->data($request));
        return redirect()->route('admin.ai.knowledge.index')->with('success', 'Đã cập nhật tài liệu.');
    }

    public function destroy(KnowledgeDoc $knowledge)
    {
        $knowledge->delete();
        return back()->with('success', 'Đã xóa tài liệu.');
    }
}
