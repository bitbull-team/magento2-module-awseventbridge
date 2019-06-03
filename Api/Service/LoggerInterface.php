<?php declare(strict_types=1);

namespace Bitbull\Mimmo\Api\Service;

interface LoggerInterface
{
    /**
     * Logging facility
     *
     * @param string $message
     * @param int $level
     * @param array $context
     */
    public function log($message, $level = null, $context = []);

    /**
     * @param string $message
     * @param array $context
     */
    public function debug($message, $context = []);

    /**
     * @param string $message
     * @param array $context
     */
    public function error($message, $context = []);

    /**
     * @param \Exception $exception
     */
    public function logException($exception);

    /**
     * @param string $message
     * @param array $context
     */
    public function warn($message, $context = []);

    /**
     * @param string $message
     * @param array $context
     */
    public function info($message, $context = []);
}
