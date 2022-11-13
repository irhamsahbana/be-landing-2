<?php

namespace App\Http\Controllers;

use App\Models\Signup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SignupController extends Controller
{
    public function index(Request $request)
    {
        // pagination
        $data = Signup::with(['industry'])->latest()->paginate(15);

        return view('pages.SignupIndex', compact('data'));
    }

    public function downloadFile(Request $request)
    {
        $path = $request->path;

        return Storage::download($path);
    }
}
