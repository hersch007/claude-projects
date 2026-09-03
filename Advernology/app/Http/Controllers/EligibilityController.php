<?php

namespace App\Http\Controllers;

use App\Http\Requests\EligibilityRequest;
use App\Services\EligibilityService;

class EligibilityController extends Controller
{
    public function __construct(private EligibilityService $service) {}

    public function check(EligibilityRequest $request)
    {
        $result = $this->service->check($request->validated('email'));

        return response()
            ->view('public.eligibility', $result)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }
}
