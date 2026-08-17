<?php

namespace App\Http\Controllers;

use App\Http\Resources\LessonResource;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index()
    {
        $lessons = Lesson::all();
        return LessonResource::collection($lessons);
    }
    public function store(Request $request)
    {
        $request->validate([
            'song' => 'required|string',
            'singer' => 'required|string',
            'instrument' => 'required|string',
            'type' => 'required|in:Arabic,International',
            'level' => 'required|in:beginner,intermediate,advanced',
            'image'=> 'nullable|image|max:2048',
            'audio' => 'nullable|file|mimes:mp3,wav',
            'video' => 'nullable|file|mimes:mp4,mov',
            'pdf' => 'nullable|file|mimes:pdf',
        ]);

        $lesson = new Lesson();
        $lesson->song = $request->song;
        $lesson->singer = $request->singer;
        $lesson->instrument = $request->instrument;
        $lesson->type = $request->type;
        $lesson->level = $request->level;

        if ($request->hasFile('image')) {
            $imagePath = saveFile($request->file('image'), 'lessons/image');
            $lesson->image = $imagePath;
        }

        if ($request->hasFile('audio')) {
            $audioPath = saveFile($request->file('audio'), 'lessons/audio');
            $lesson->audio = $audioPath;
        }

        if ($request->hasFile('video')) {
            $videoPath = saveFile($request->file('video'), 'lessons/video');
            $lesson->video = $videoPath;
        }

        if ($request->hasFile('pdf')) {
            $pdfPath = saveFile($request->file('pdf'), 'lessons/pdf');
            $lesson->pdf = $pdfPath;
        }

        $lesson->save();

        return response()->json($lesson, 201);
    }
}
