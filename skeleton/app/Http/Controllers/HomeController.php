<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Slendie\Http\ViewResponse;

class HomeController extends Controller
{
    public function index(): ViewResponse
    {
        return response()->view('home');
    }

    public function thanks(): ViewResponse
    {
        return response()->view('thanks');
    }
}
