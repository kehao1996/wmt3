<?php

declare(strict_types = 1);

namespace App\Exception\Handler;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;


class AppTokenValidExceptionHandler extends ExceptionHandler {


    public function handle(Throwable $throwable, ResponseInterface $response) {
//        $this->stopPropagation();
//        $result = $this->helper->error(Code::UNAUTHENTICATED, $throwable->getMessage());
//        return $response->withStatus($throwable->getCode())
//            ->withAddedHeader('content-type', 'application/json')
//            ->withBody(new SwooleStream($this->helper->jsonEncode($result)));




        return $response->withHeader("Server", "Hyperf")->withStatus(403)->withBody(new SwooleStream('未登录'));
    }

    public function isValid(Throwable $throwable): bool {
        return $throwable instanceof TokenValidException;
    }

}