<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Basic ')) {
            return $this->unauthorizedResponse();
        }

        $encoded = substr($header, 6);

        $decoded = base64_decode($encoded, true);

        if (! $decoded || ! str_contains($decoded, ':')) {
            return $this->unauthorizedResponse();
        }

        [$username, $password] = explode(':', $decoded, 2);

        $expectedUsername = config('admin.username');
        $expectedPassword = config('admin.password');

        if ($username !== $expectedUsername || $password !== $expectedPassword) {
            return $this->unauthorizedResponse();
        }

        return $next($request);
    }

    protected function unauthorizedResponse(): Response
    {
        return response()
            ->json(['message' => 'Admin credentials required.'], Response::HTTP_UNAUTHORIZED)
            ->header('WWW-Authenticate', 'Basic realm="Vyom Admin"');
    }
}
