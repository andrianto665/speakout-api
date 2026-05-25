<?php
// File: app/Http/Controllers/Api/AdminContentController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminContentController extends Controller
{
    /**
     * GET: List semua content untuk course tertentu
     */
    public function index($courseId)
    {
        try {
            // Cek course ada atau tidak
            $course = \App\Models\Course::findOrFail($courseId);
            
            // Ambil semua meetings/lessons untuk course ini, urut berdasarkan order_number
            $content = Meeting::where('course_id', $courseId)
                ->orderBy('order_number')
                ->get();
            
            return response()->json([
                'course_id' => $courseId,
                'course_title' => $course->title,
                'meetings' => $content
            ]);
            
        } catch (\Exception $e) {
            Log::error('AdminContentController@index error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to load content', 
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST: Tambah content baru (meeting atau lesson)
     */
    public function store(Request $request, $courseId)
    {
        try {
            // Validasi input
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'type' => 'required|in:normal,test,final',
                'content' => 'nullable|url',
                'order_number' => 'required|integer|min:1'
            ], [
                'type.in' => 'Type must be one of: normal, test, final'
            ]);
            
            // Create new meeting/lesson
            $item = Meeting::create([
                'course_id' => $courseId,
                'title' => $validated['title'],
                'type' => $validated['type'],
                'content' => $validated['content'] ?? null,
                'order_number' => $validated['order_number']
            ]);
            
            return response()->json($item, 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed', 
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('AdminContentController@store error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create content', 
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * PUT: Update content existing
     */
    public function update(Request $request, $id)
    {
        try {
            // Cari item yang mau diupdate
            $item = Meeting::findOrFail($id);
            
            // Validasi input
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'type' => 'required|in:normal,test,final',
                'content' => 'nullable|url',
                'order_number' => 'required|integer|min:1'
            ]);
            
            // Update data
            $item->update([
                'title' => $validated['title'],
                'type' => $validated['type'],
                'content' => $validated['content'] ?? null,
                'order_number' => $validated['order_number']
            ]);
            
            return response()->json($item);
            
        } catch (\Exception $e) {
            Log::error('AdminContentController@update error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update content', 
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * DELETE: Hapus content
     */
    public function destroy($id)
    {
        try {
            $item = Meeting::findOrFail($id);
            $item->delete();
            
            return response()->json(['message' => 'Deleted successfully']);
            
        } catch (\Exception $e) {
            Log::error('AdminContentController@destroy error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete content', 
                'error' => $e->getMessage()
            ], 500);
        }
    }
}