<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::get()->toTree();
        return view('categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $parentCategories = Category::whereNull('parent_id')->get();
        return view('categories.create',['editMode' => false, 'parentCategories' => $parentCategories]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|unique:categories',
            'description' => 'nullable',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $category = new Category;
        $category->name = $request->name;
        $category->description = $request->description;
        $category->parent_id = $request->parent_id;
        $category->save();

        return redirect()->route('categories.index')->with('success', 'تم إضافة الفئة بنجاح.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        $parentCategories = Category::whereNull('parent_id')->get();
        return view('categories.edit', ['category' => $category, 'parentCategories' => $parentCategories, 'editMode' => true]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        // 1. التحقق من صحة البيانات
        $validatedData = $request->validate([
            'name' => 'required|unique:categories,name,' . $category->id,
            'description' => 'nullable',
            'parent_id' => 'nullable|exists:categories,id',
        ]);
    
        // 2. تحديث خصائص الفئة
        $category->name = $validatedData['name'];
        $category->description = $validatedData['description'];
    
         // 3. تحديث الفئة الأب (معالجة العلاقات الهيكلية)
         if ($validatedData['parent_id'] != $category->parent_id) {
             $category->parent_id = $validatedData['parent_id'];
             // إذا كنت تستخدم kalnoy/nestedset:
             $category->saveAsRoot(); // أو أي طريقة أخرى مناسبة لتحديث الشجرة
         } else {
             $category->save();
         }
        
    
        // 4. إعادة التوجيه مع رسالة نجاح
        return redirect()->route('categories.index')->with('success', 'تم تعديل الفئة بنجاح.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        if ($category->children->count() > 0) {
            return back()->withErrors(['error' => 'لا يمكن حذف فئة تحتوي على فئات فرعية.']);
        }
    
        $category->delete();
    
        return redirect()->route('categories.index')->with('success', 'تم حذف الفئة بنجاح.');
    }
}
