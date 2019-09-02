<?php
namespace HuangZx;

use Closure;
use ReflectionException;
use ReflectionFunction;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use function get_class;
use function gettype;
use function spl_object_id;
use function var_export;

function set_path_cut_len(?int $clen = null): int
{
    static $len = 0;
    if (null !== $clen) {
        $len = $clen;
    }
    return $len;
}

/**
 * @param      $data
 * @param bool $display
 * @return mixed
 */
function debug_array(iterable $data, bool $display = false)
{
    foreach ($data as &$item) {
        if (is_array($item)) {
            $item = debug_array($item, $display);
        } else {
            $item = debug_value($item);
        }
    }
    $content = $data;

    if ($display) {
        echo var_export($content, true);
    }
    return $content;
}

function debug_value($val)
{
    static $cloner = null;
    static $dumper = null;
    if (null === $dumper) {
        $cloner = new VarCloner();
        // $cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO); // 用途未知
        // TODO 使用了外部资源，可能会导致 Swoole 协程兼容问题
        $dumper = new CliDumper();
    }

    switch (gettype($val)) {
        case 'resource':
        case 'resource (closed)':
        case 'unknown type':
            return $dumper->dump($cloner->cloneVar($val), true);
        case 'object':
            if ($val instanceof Closure) {
                return debug_closure($val, false);
            }
            return '\\' . get_class($val) . '#' . spl_object_id($val);
    }

    return $val;
}

/**
 * @param      $object
 * @param bool $display
 * @return string|null
 */
function debug_closure(Closure $object, bool $display = false)
{
    $content = null;
    if (false === $object instanceof Closure) {
        $content = 'debug: ' . gettype($object) . PHP_EOL;
    } else {
        try {
            $ref = new ReflectionFunction($object);
        } catch (ReflectionException $e) {
            return null;
        }
        if ($ref->getClosureThis()) {
            $thisClass = get_class($ref->getClosureThis());
        } else {
            $thisClass = substr($ref->getFileName(), set_path_cut_len());
        }

        $content = "$thisClass@{$ref->getStartLine()}-{$ref->getEndLine()}\n";
    }
    if ($display) {
        echo $content;
    }
    return $content;
}