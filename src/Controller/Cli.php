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
    public int   $redirects = 0;
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

    // Appends address in crawler queue
    public function addSource(
        string $url
    ): bool
    {
        // Validate given value and check it is unique in the pool
        if ($this->_source($url) && !in_array($url, $this->source))
        {
            $this->source[] = $url;

            return true;
        }

        return false;
    }

    // Updates address in crawler queue
    public function putSource(
        int $position,
        string $url
    ): bool
    {
        if (in_array($url, $this->source))
            return true;

        if ($this->_source($url))
            return !array_splice($this->source, $position, 0, [$position => $url]);

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

        // Route
        switch ($response->getCode())
        {
            case 20: // success

                // Reset redirection counter
                $this->redirects = 0;

                print(
                    Message::magenta(
                        sprintf(
                            _("\tcode: %d (success)"),
                            $response->getCode()
                        )
                    )
                );

            break;

            case 30: // redirection
            case 31:

                // Increase redirection counter
                $this->redirects++;

                // Print event debug
                print(
                    Message::yellow(
                        sprintf(
                            _("\tcode: %d (redirection #%d)"),
                            $response->getCode(),
                            $this->redirects
                        )
                    )
                );

                print(
                    Message::yellow(
                        sprintf(
                            _("\tmeta: %s"),
                            $response->getMeta()
                        )
                    )
                );

                print(
                    Message::yellow(
                        sprintf(
                            _("\ttime: %f -d %f"),
                            $time,
                            $this->option->delay + $time
                        )
                    )
                );

                {
                    $count = count($this->source); // calculate shared value once

                    print(
                        Message::yellow(
                            sprintf(
                                _("\tleft: %d/%d total (%d processed)"),
                                $count - $offset,
                                $count,
                                $offset
                            )
                        )
                    );
                }

                // Crawl next address...
                if ($this->option->crawl)
                {
                    if ($this->redirects <= $this->option->follow)
                    {
                        // Validate redirection target location
                        if (filter_var($response->getMeta(), FILTER_VALIDATE_URL)) // @TODO resolve relative locations
                        {
                            // Insert redirection target to the next destination
                            if (!$this->putSource($offset + 1, trim($response->getMeta())))
                            {
                                print(
                                    Message::red(
                                        sprintf(
                                            _("\tdestination could not be updated due to the conditions or it has already been indexed: `%s`"),
                                            $response->getMeta()
                                        )
                                    )
                                );
                            }
                        }
                        else
                        {
                            print(
                                Message::red(
                                    sprintf(
                                        _("\tskip invalid redirection URL: `%s`"),
                                        $response->getMeta()
                                    )
                                )
                            );
                        }
                    }
                    else
                    {
                        print(
                            Message::red(
                                sprintf(
                                    _("\tredirection limit (%d) reached, continue next address in queue"),
                                    $this->option->follow
                                )
                            )
                        );
                    }

                    // Continue next location
                    $this->start(
                        $offset + 1
                    );
                }

                return; // panic? @TODO

            break;
            default: // failure

                print(
                    Message::red(
                        sprintf(
                            _("\tcode: %d (unsupported)"),
                            intval(
                                $response->getCode()
                            )
                        )
                    )
                );

                // Reset redirection counter
                $this->redirects = 0;

                // Crawl next address...
                if ($this->option->crawl)
                {
                    $this->start(
                        $offset + 1
                    );
                }

                return; // panic @TODO
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

        {
            $count = count($this->source); // calculate shared value once

            print(
                Message::magenta(
                    sprintf(
                        _("\tleft: %d/%d total (%d processed)"),
                        $count - $offset,
                        $count,
                        $offset
                    )
                )
            );
        }

        // Set data mode
        $raw = ($this->option->raw || !str_contains((string) $response->getMeta(), 'text/gemini'));

        // Parse gemtext
        if (!$raw && $this->option->crawl)
        {
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

                // Check link match common source rules
                if (!$this->_source($address->get()))
                {
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
                        $local = $this->filesystem->getFilenameRelativeToDirname(
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