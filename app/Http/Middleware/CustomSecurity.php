<?php
   // app/Http/Middleware/CustomSecurity.php

   namespace App\Http\Middleware;

   use Closure;
   use Illuminate\Http\Request;

   class CustomSecurity
   {
       public function handle(Request $request, Closure $next)
       {
           $token = env('CUSTOM_SECURITY_TOKEN');

           if ($request->header('X-CRE-Token') !== $token) {
               return response('Unauthorized.', 401);
           }

           return $next($request);
       }
   }