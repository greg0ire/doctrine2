<?php

namespace Doctrine\ORM;

final class DeprecatedClassesRegistry
{

    /**
     * Format:
     * DeprecatedClass => [
     *    NewClass,
     *    ['will', 'be', 'passed', 'to', 'the', 'callable'],
     *    SomeCallable,
     * ]
     */
    private $registry;

    public function __construct()
    {
        $this->registry = [];
    }

    /**
     * registers several classes with the same callable
     *
     * @param $classes array a hash in the following format: [
     *    DeprecatedClass => [
     *        NewClass,
     *        ['will', 'be', 'passed', 'to', 'the', 'callable'],
     *    ]
     * ]
     */
    public function registerClasses(array $classes, ?callable $formatter = null): void
    {
        foreach ($classes as $class => $metadata) {
            if (count($metadata) === 1) {
                $metadata[] = [];
            }
            [$inFavorOf, $formatterParameters] = $metadata;
            $this->registerClass($class, $inFavorOf, $formatterParameters, $formatter);
        }
    }

    public function registerClass(
        string $class,
        string $inFavorOf,
        array $extraFormatterParameters = [],
        ?callable $formatter = null
    ): void {
        $this->registry[$class] = [$inFavorOf, $extraFormatterParameters, $formatter];
    }

    public function autoload(string $class)
    {
        if (array_key_exists($class, $this->registry)) {
            [$inFavorOf, $extraCallableParameters, $callable] = $this->registry[$class];
            if ($callable === null) {
                $callable = [$this, 'defaultFormatter'];
            }
            @trigger_error(
                call_user_func($callable, $class, $inFavorOf, ...$extraCallableParameters),
                E_USER_DEPRECATED
            );
            class_alias($inFavorOf, $class);
        }
    }

    private function defaultFormatter(
        string $deprecatedClass,
        string $inFavorOf,
        string $since,
        string $removalPlannedOn
    ): string {
        return sprintf(
            'Class %s is deprecated in favor of class %s since %s, will be removed in %s.',
            $deprecatedClass,
            $inFavorOf,
            $since,
            $removalPlannedOn
        );
    }
}
