<?php

namespace App\Http\Controllers\Api;

use App\Domains\Tenancy\Actions\RegisterClinic;
use App\Domains\Tenancy\Support\ClinicUrl;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterClinicRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ClinicRegistrationController extends Controller
{
    /** Registers a clinic and returns its login URL. */
    public function store(RegisterClinicRequest $request, RegisterClinic $registerClinic): JsonResponse
    {
        $data = $request->toData();
        $tenant = $registerClinic->handle($data);

        return response()->json([
            'slug' => $tenant->slug(),
            'loginUrl' => ClinicUrl::to($tenant, '/login', ['cadastro' => '1', 'email' => $data->email]),
        ], Response::HTTP_CREATED);
    }
}
