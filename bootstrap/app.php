<?php

use App\Http\Middleware\CheckSocketIdMiddleware;
use App\Http\Middleware\CorsMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
                  ->withRouting(
                      web:      __DIR__ . '/../routes/web.php',
                      api:      __DIR__ . '/../routes/api.php',
                      commands: __DIR__ . '/../routes/console.php',
                      health:   '/up',
                  )
                  ->withMiddleware(function (Middleware $middleware) {
                      $middleware->redirectGuestsTo(fn (Request $request) => error('Unauthorized', 401));
                      $middleware->append(CorsMiddleware::class);
                      $middleware->alias([
                                             'abilities'     => CheckAbilities::class,
                                             'ability'       => CheckForAnyAbility::class,
                                             'checkSocketId' => CheckSocketIdMiddleware::class,
                                         ]);
                  })
                  ->withExceptions(function (Exceptions $exceptions) {
                      $exceptions->render(function (Exception $e, Request $request) {
                          $message = $e->getMessage();
                          $code = $e->getCode();
                          $status = 400;
                          if ($request->is('api/*')) {

                              return api(NULL, $message, $code, $status);

                          }
                      });
                  })->create();
