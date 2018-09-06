<?php

namespace Parser\Errors;

class ErrorHandler
{
    /**
     * @param $error
     * @return string
     */
    static public function getErrorName($error)
    {
        $errors = [
            E_ERROR             => 'ERROR',
            E_PARSE             => 'PARSE',
            E_NOTICE            => 'NOTICE',
            E_CORE_ERROR        => 'CORE_ERROR',
            E_CORE_WARNING      => 'CORE_WARNING',
            E_COMPILE_ERROR     => 'COMPILE_ERROR',
            E_COMPILE_WARNING   => 'COMPILE_WARNING',
            E_USER_ERROR        => 'USER_ERROR',
            E_USER_WARNING      => 'USER_WARNING',
            E_USER_NOTICE       => 'USER_NOTICE',
            E_STRICT            => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED        => 'DEPRECATED',
            E_USER_DEPRECATED   => 'USER_DEPRECATED',
        ];
        if (array_key_exists($error, $errors)) {
            return $errors[$error] . " [$error]";
        }

        return $error;
    }

    public function register()
    {
        ini_set('display_errors', 'on');
        error_reporting(E_ALL | E_STRICT);
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
        register_shutdown_function([$this, 'fatalErrorHandler']);
    }

    /**
     * @param string $errno
     * @param string $errstr
     * @param string $file
     * @param string $line
     * @return bool
     */
    public function errorHandler($errno, $errstr, $file, $line)
    {
        $this->showError($errno, $errstr, $file, $line);
        return false;
    }

    /**
     * @param \Exception $e
     */
    public function exceptionHandler($e)
    {
        if ($e instanceof \Exception) {
            $this->showError($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), 404);
        }
    }

    public function fatalErrorHandler()
    {
        if ($error = error_get_last() AND $error['type'] & (E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR)) {
            $this->showError($error['type'], $error['message'], $error['file'], $error['line'], 500);
        }
    }

    /**
     * @param string $errno
     * @param string $errstr
     * @param string $file
     * @param string $line
     * @param int $status
     */
    public function showError($errno, $errstr, $file, $line, $status = 500)
    {
        echo $message = self::getErrorName($errno) . " " . $errstr . ' file: ' . $file . ' line: ' . $line;
        echo '\n';
    }
}
