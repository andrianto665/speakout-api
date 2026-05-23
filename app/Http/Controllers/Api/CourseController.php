<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Course;

class CourseController extends Controller {
    public function index() {
        return response()->json(Course::with('lessons')->get());
    }
    public function show($id) {
        return response()->json(Course::with('lessons')->findOrFail($id));
    }
}