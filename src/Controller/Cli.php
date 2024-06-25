<?php

declare(strict_types=1);

namespace Yggverse\GeminiDL\Controller;

use \Yggverse\GeminiDL\Model\Cli\Message;
use \Yggverse\GeminiDL\Model\Cli\Option;
use \Yggverse\GeminiDL\Model\Filesystem;

use \Yggverse\Gemini\Client\Request;
use \Yggverse\Gemini\Client\Response;
use \Yggverse\Gemtext\Document;
use \Yggverse\Net\Address;

class Cli
{
    // Init totals
    public int   $skip = 0;
    public int   $fail = 0;
    public int   $save = 0;
    public int   $size = 0;
    public float $time = 0;

    // Pool for crawler queue
    public array $source = [];

    // Define model helpers
    public \Yggverse\GeminiDL\Model\Filesystem $filesystem;
    public \Yggverse\GeminiDL\Model\Cli\Option $option;

    // Init CLI object using options preset
    public function __construct(
        array $config // getopt
    ) {
        // Init options
        $this->option = new Option(
            $config
        );

        // Init local filesystem location
        $this->filesystem = new Filesystem(
            $this->option->target,
            $this->option->unique ? time() : null // snap version
        );

        // Append source address to crawler queue
        $this->addSource(
            $this->option->source
        );
    }

    // Appends valid address to crawler queue
    public function addSource(
        string $url
    ): bool
    {
        // Validate given value and check it is unique in pool
        if ($this->_source($url) && !in_array($url, $this->source))
        {
            $this->source[] = $url;

            return true;
        }

        return false;
    }

    // Begin crawler task
    public function start(
        int $offset = 0
    ): void
    {
        // Apply delay to prevent source overload
        if ($offset)
        {
            sleep(
                $this->option->delay
            );
        }

        // Check for crawl queue completed
        if (!isset($this->source[$offset]))
        {
            print(
                $this->_summary()
            );

            return; // stop
        }

        // Dump source address
        print(
            Message::blue(
                $this->source[$offset],
                true
            )
        );

        // Parse source address
        $source = new Address(
            $this->source[$offset]
        );

        // Build filesystem location
        $filename = $this->filesystem->getFilenameFromNetAddress(
            $source,
            $this->option->index
        );

        // Build request
        $request = new Request(
            $source->get()
        );

        // Track request time
        $time = microtime(true);

        // Parse response
        $response = new Response(
            $bin = $request->getResponse()
        );

        // Calculate response time
        $this->time += $time = microtime(true) - $time; // @TODO to API

        // Check response code success
        if (20 === $response->getCode())
        {
            print(
                Message::magenta(
                    sprintf(
                        _("\tcode: %d"),
                        $response->getCode()
                    )
                )
            );
        }

        else
        {
            print(
                Message::red(
                    sprintf(
                        _("\tcode: %d"),
                        intval(
                            $response->getCode()
                        )
                    )
                )
            );

            // Crawl next address...
            if ($this->option->crawl)
            {
                $this->start(
                    $offset + 1
                );
            }
        }

        // Calculate document size
        $this->size += $size = (int) strlen($bin);

        print(
            Message::magenta(
                sprintf(
                    _("\tsize: %d"),
                    $size
                )
            )
        );

        // Get meta headers info
        if ($response->getMeta())
        {
            print(
                Message::magenta(
                    sprintf(
                        _("\tmeta: %s"),
                        $response->getMeta()
                    )
                )
            );
        }

        print(
            Message::magenta(
                sprintf(
                    _("\ttime: %f -d %f"),
                    $time,
                    $this->option->delay + $time
                )
            )
        );

        // Set data mode
        $raw = ($this->option->raw || !str_contains((string) $response->getMeta(), 'text/gemini'));

        // Parse gemtext
        if (!$raw && $this->option->crawl)
        {
            // Reset skipped links
            $skip = 0;

            // Parse gemtext content
            $document = new Document(
                $response->getBody()
            );

            // Get link entities
            foreach ($document->getLinks() as $link)
            {
                // Build new address
                $address = new Address(
                    $link->getAddress()
                );

                // Make relative links absolute
                $address->toAbsolute(
                    $source
                );

                // Check link match common source rules, @TODO --external links
                if (!$this->_source($address->get()) || $address->getHost() != $source->getHost())
                {
                    $skip++;

                    $this->skip += $skip;

                    continue;
                }

                // Address --keep not requested
                if (!$this->option->keep)
                {
                    // Generate absolute local file name
                    $local = $this->filesystem->getFilenameFromNetAddress(
                        $address,
                        $this->option->index,
                    );

                    // Absolute option skipped, make local path relative
                    if (!$this->option->absolute)
                    {
                        $local = Filesystem::getFilenameRelativeToDirname(
                            $local,
                            dirname(
                                $filename
                            )
                        );
                    }
                    // Replace link to local path
                    $link->setAddress(
                        $local
                    );
                }

                // Append new address to crawler pool
                $this->addSource(
                    $address->get()
                );
            }
        }

        // Save document to file
        if ($this->filesystem->save($filename, $raw || empty($document) ? $response->getBody()
                                                                        : $document->toString())
        ) {
            print(
                Message::green(
                    _("\tsave: ") . $filename
                )
            );

            $this->save++;
        }

        else
        {
            print(
                Message::red(
                    _("\tfail: ") . $filename
                )
            );

            $this->fail++;
        }

        // Crawl mode enabled
        if ($this->option->crawl)
        {
            // Crawl next
            $this->start(
                $offset + 1
            );
        }
    }

    public static function exception(
        string $message,
        ?string $help = null
    ): void
    {
        print(
            Message::red(
                $message
            )
        );

        if ($help)
        {
            print(
                Message::plain(
                    $help
                )
            );
        }
    }

    // Local helpers
    private function _source(
        string $value
    ): bool
    {
        // Supported Gemini protocol links only
        if(!str_starts_with($value, 'gemini://'))
        {
            return false;
        }

        // Make sure link --match option
        if (!preg_match($this->option->match, $value))
        {
            return false;
        }

        return true;
    }

    private function _summary(): string
    {
        return implode(
            '',
            [
                Message::blue(
                    _('----------------')
                ),
                Message::blue(
                    _('crawl completed!'),
                    true
                ),
                Message::magenta(
                    sprintf(
                        _("\tdocs: %d"),
                        count(
                            $this->source
                        )
                    )
                ),
                Message::magenta(
                    sprintf(
                        _("\tsave: %d"),
                        $this->save
                    )
                ),
                Message::magenta(
                    sprintf(
                        _("\tskip: %d"),
                        $this->skip
                    )
                ),
                Message::magenta(
                    sprintf(
                        _("\tfail: %d"),
                        $this->fail
                    )
                ),
                Message::magenta(
                    sprintf(
                        _("\tsize: %d"),
                        $this->size
                    )
                ),
                Message::magenta(
                    sprintf(
                        _("\ttime: %f -d %f"),
                        $this->time,
                        $this->option->delay * count(
                            $this->source
                        ) + $this->time
                    )
                )
            ]
        );
    }
}