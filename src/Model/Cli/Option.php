<?php

declare(strict_types=1);

namespace Yggverse\GeminiDL\Model\Cli;

class Option
{
    public bool   $crawl    = false;
    public int    $delay    = 1;
    public bool   $external = false;
    public int    $follow   = 0;
    public bool   $help     = false;
    public string $index    = 'index.gmi';
    public bool   $keep     = false;
    public int    $level    = 0;
    public string $match    = '/.*/';
    public bool   $raw      = false;
    public string $source;
    public string $target;
    public bool   $unique   = false;

    public function __construct(
        array $options
    )
    {
        if (empty($options))
        {
            throw new \Exception(
                _('Options required, run --help')
            );
        }

        // Define variables
        $this->crawl = boolval(
            isset($options['crawl']) || isset($options['c']) || $this->crawl
        );

        $this->delay = intval(
            $options['delay'] ?? $options['d'] ?? $this->delay
        );

        $this->external = boolval(
            isset($options['external']) || isset($options['e']) || $this->external
        );

        $this->follow = intval(
            $options['follow'] ?? $options['f'] ?? $this->follow
        );

        $this->help = boolval(
            isset($options['help']) || isset($options['h']) || $this->help
        );

        $this->index = strval(
            $options['index'] ?? $options['i'] ?? $this->index
        );

        $this->keep = boolval(
            isset($options['keep']) || isset($options['k']) || $this->keep
        );

        $this->level = intval(
            $options['level'] ?? $options['l'] ?? $this->level
        );

        $this->match = strval(
            $options['match'] ?? $options['m'] ?? $this->match
        );

        $this->raw = boolval(
            isset($options['raw']) || isset($options['r']) || $this->raw
        );

        $this->source = strval(
            $options['source'] ?? $options['s'] ?? null
        );

        $this->target = strval(
            $options['target'] ?? $options['t'] ?? null
        );

        $this->unique = boolval(
            isset($options['unique']) || isset($options['u']) || $this->unique
        );

        // Throw help @TODO
        if ($this->help)
        {
            throw new \Exception;
        }

        // Validate source
        if (empty($this->source))
        {
            throw new \Exception(
                _('--source argument required!')
            );
        }

        if(!str_starts_with($this->source, 'gemini://'))
        {
            throw new \Exception(
                _('--source protocol not supported!')
            );
        }

        if (!preg_match($this->match, $this->source))
        {
            throw new \Exception(
                _('--source does not --match condition!')
            );
        }

        // Validate target
        if (empty($this->target))
        {
            throw new \Exception(
                _('--target argument required!')
            );
        }

        if (!is_dir($this->target))
        {
            throw new \Exception(
                _('--target location not exists!')
            );
        }

        if (!is_readable($this->target))
        {
            throw new \Exception(
                _('--target location not readable!')
            );
        }

        if (!is_writable($this->target))
        {
            throw new \Exception(
                _('--target location not writable!')
            );
        }

        // Validate index
        if (!$extension = pathinfo($this->index, PATHINFO_EXTENSION))
        {
            throw new \Exception(
                _('--index filename must have extension!')
            );
        }
    }
}