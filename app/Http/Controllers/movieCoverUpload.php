<?php
namespace App\Http\Controllers;


use Illuminate\Support\Facades\Http;
use App\Jobs\askLLM;
class movieCoverUpload extends Controller
{
    public function ai_call()
    {

        askLLM::dispatch();

    }
}
