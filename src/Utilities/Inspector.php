<?php namespace Lit\Nexus\Utilities;

class Inspector
{
    protected const EXCEPTION_MESSAGE = <<<HTML
<h2>PHP Fatal error</h2>
<xmp>
%s
</xmp>
HTML;

    protected const THROWABLE_DESC = <<<TEXT
%s with message '%s' in %s:%s

Stack trace:
%s

thrown in %s on line %s
TEXT;

    protected const THROWABLE_DIVIDER = "\n\n******Previous******\n";

    public static function setGlobalHandler()
    {
        set_error_handler([static::class, 'errorHandler']);
        set_exception_handler([static::class, 'exceptionHandler']);
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting
            return null;
        }

        throw new \ErrorException($errstr, $errno, 1, $errfile, $errline);
    }


    /**
     * @param \Exception $exception
     */
    public static function exceptionHandler($exception)
    {
        $msg = sprintf(
            static::EXCEPTION_MESSAGE,
            static::formatThrowable($exception)
        );

        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }

        echo $msg;

        exit(255);
    }

    public static function formatThrowable(\Throwable $throwable)
    {
        $trace = $throwable->getTrace();

        $result = static::formatTrace($trace);

        $msg = sprintf(
            static::THROWABLE_DESC,
            get_class($throwable),
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine(),
            implode("\n", $result),
            $throwable->getFile(),
            $throwable->getLine()
        );

        if ($previous = $throwable->getPrevious()) {
            $msg .= static::THROWABLE_DIVIDER . static::formatThrowable($previous);
        }

        return $msg;
    }

    public static function formatTrace($trace)
    {
        $result = array();
        $traceline = '#%s %4$s(%5$s) @ %2$s:%3$s';
        $key = 0;
        foreach ($trace as $key => $stackPoint) {

            if (isset($stackPoint['args'])) {
                foreach ($stackPoint['args'] as $k => $arg) {
                    unset($stackPoint['args'][$k]); //args下可能有引用，先unset防止串改
                    $stackPoint['args'][$k] = static::formatArg($arg);
                }
            } else {
                $stackPoint['args'] = array();
            }
            unset($arg);
            $fn = isset($stackPoint['class'])
                ? "{$stackPoint['class']}{$stackPoint['type']}{$stackPoint['function']}"
                : $stackPoint['function'];

            $result[] = sprintf(
                $traceline,
                $key,
                @$stackPoint['file'],
                @$stackPoint['line'],
                $fn,
                implode(', ', $stackPoint['args'])
            );
        }

        $result[] = '#' . ++$key . ' {main}';
        return $result;
    }

    protected static function friendlyErrorType($type)
    {
        switch ($type) {
            case E_ERROR: // 1 //
                return 'E_ERROR';
            case E_WARNING: // 2 //
                return 'E_WARNING';
            case E_PARSE: // 4 //
                return 'E_PARSE';
            case E_NOTICE: // 8 //
                return 'E_NOTICE';
            case E_CORE_ERROR: // 16 //
                return 'E_CORE_ERROR';
            case E_CORE_WARNING: // 32 //
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR: // 64 //
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING: // 128 //
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR: // 256 //
                return 'E_USER_ERROR';
            case E_USER_WARNING: // 512 //
                return 'E_USER_WARNING';
            case E_USER_NOTICE: // 1024 //
                return 'E_USER_NOTICE';
            case E_STRICT: // 2048 //
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR: // 4096 //
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192 //
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED: // 16384 //
                return 'E_USER_DEPRECATED';
        }
        return "";
    }

    protected static function formatArg($arg)
    {
        return is_scalar($arg) ? var_export($arg, true) : (is_object($arg) ? get_class(
            $arg
        ) : gettype($arg));
    }
}
