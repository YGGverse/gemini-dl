<?php

declare(strict_types=1);

namespace Yggverse\GeminiDL\Model;

class Filesystem
{
    private string $_filepath;

    public function __construct(
        string $directory,
        ?int $version
    ) {
        switch (true)
        {
            case empty($directory):

                throw new \Exception(
                    _('Directory required')
                );

            break;

            case !is_dir($directory):

                throw new \Exception(
                    _('Directory does not exist')
                );

            break;

            case !is_readable($directory):

                throw new \Exception(
                    _('Directory not readable')
                );

            break;

            case !is_writable($directory):

                throw new \Exception(
                    _('Directory not writable')
                );

            break;

            default:

                $this->_filepath = realpath(
                    $directory
                ) . DIRECTORY_SEPARATOR;

                if ($version)
                {
                    $this->_filepath .= $version . DIRECTORY_SEPARATOR;
                }
        }
    }

    public function getFilepath(): string
    {
        return $this->_filepath;
    }

    public function getFilenameFromNetAddress(
        \Yggverse\Net\Address $address,
        ?string $index = null
    ): ?string
    {
        switch (true)
        {
            case empty($address->get()):

                throw new \Exception(
                    _('Incorrect target address')
                );

                return null;

            break;

            case empty($address->getScheme()):

                throw new \Exception(
                    _('Scheme required for target address')
                );

                return null;

            break;
        }

        $filename = $this->_filepath . str_replace(
            [
                $address->getScheme() . '://',
                '/'
            ],
            [
                null,
                DIRECTORY_SEPARATOR
            ],
            $address->get()
        );

        if ($index && (str_ends_with($filename, '/') || !pathinfo($filename, PATHINFO_EXTENSION) || basename($filename) == $address->getHost()))
        {
            $filename = rtrim(
                $filename,
                DIRECTORY_SEPARATOR
            ) . DIRECTORY_SEPARATOR . $index;
        }

        if (is_dir($filename))
        {
            throw new \Exception(
                _('Target filename linked to directory')
            );
        }

        return $filename;
    }

    public function save(
        string $filename,
        string $data
    ): bool
    {
        if (!str_starts_with($filename, $this->_filepath))
        {
            throw new \Exception(
                _('Target filename out of storage location')
            );
        }

        $filepath = str_replace(
            basename(
                $filename
            ),
            '',
            $filename
        );

        @mkdir(
            $filepath,
            0777, // @TODO be careful with leading zero
            true
        );

        if (!is_dir($filepath))
        {
            throw new \Exception(
                _('Could not create target directory')
            );
        }

        if (!is_writable($filepath))
        {
            throw new \Exception(
                _('Target directory is not readable')
            );
        }

        if (!is_writable($filepath))
        {
            throw new \Exception(
                _('Target directory is not writable')
            );
        }

        return (bool) file_put_contents(
            $filename,
            $data
        );
    }
}