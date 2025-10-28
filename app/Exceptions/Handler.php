<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // لاگ پیش‌فرض کافی است؛ کانال و سطح از .env می‌آید
        });

        $this->renderable(function (Throwable $e, Request $request) {
            // فقط وقتی دیباگ خاموش است، صفحه‌های دوستانه نشان بده
            if (!config('app.debug')) {

                // اگر خطای HTTP استاندارد باشد (404/419/429/503 ...) اجازه بده ویوی همان کد رندر شود
                if ($this->isHttpException($e)) {
                    /** @var HttpExceptionInterface $e */
                    return $this->renderHttpException($e);
                }

                // برای خطاهای دیگر، یک 500 با کد پیگیری یکتا
                $exceptionId = Str::uuid()->toString();

                Log::error('Unhandled exception', [
                    'exception_id' => $exceptionId,
                    'url'         => $request->fullUrl(),
                    'user_id'     => optional($request->user())->id,
                    'method'      => $request->method(),
                    'ip'          => $request->ip(),
                    'message'     => $e->getMessage(),
                    'trace_head'  => collect($e->getTrace())->take(3), // خلاصهٔ کم‌خطر
                ]);

                return response()->view('errors.500', compact('exceptionId'), 500);
            }
        });
    }
}
