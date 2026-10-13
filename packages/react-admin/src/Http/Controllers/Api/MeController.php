<?php

declare(strict_types=1);

namespace Slendie\ReactAdmin\Http\Controllers\Api;

use Slendie\Http\JsonResponse;
use Slendie\Http\Request;
use Slendie\ReactAdmin\Support\UserPayload;

final class MeController
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(UserPayload::from($request->user()));
    }
}
