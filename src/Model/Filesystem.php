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

    /*
     * Return --target realpath
     *
     * Currently not in use.
     */
    public function getFilepath(): string
    {
        return $this->_filepath;
    }

    /*
     * Build local filepath from \Yggverse\Net\Address
     *
     * Return absolute filename using --target defined
     * Method doesn't check result location for exist.
     */
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

    /*
     * Save data string to destination.
     *
     * This method also builds recursive directory path
     * and overwrites existing files (data) on exist.
     *
     * $filename must start from --target defined
     * $data is plain gemtext or binary (media) string
     */
    public function save(
        string $filename,
        string $data
    ): bool
    {
        if (!str_starts_with($filename, $this->_filepath))
        {
            throw new \Exception(
                sprintf(
                    _('Filename "%s" out of filesystem root "%s"'),
                    $filename,
                    $this->_filepath
                )
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

    /*
     * Build relative $filename path to given $dirname
     *
     * Method does not and must not check location for exist
     * $filename and $dirname must contain --target defined.
     *
     * This implementation compatible with --external option
     * resulting path get format: ../domain.com/path/to/file
     */
    public function getFilenameRelativeToDirname(
        string $filename,
        string $dirname
    ): string
    {
        // Require absolute $filename
        if (!str_starts_with($filename, DIRECTORY_SEPARATOR))
        {
            throw new \Exception(
                'Absolute filename required'
            );
        }

        // Require absolute $dirname
        if (!str_starts_with($dirname, DIRECTORY_SEPARATOR))
        {
            throw new \Exception(
                'Absolute dirname required'
            );
        }

        // Require valid $filename root location
        if (!str_starts_with($filename, $this->_filepath))
        {
            throw new \Exception(
                sprintf(
                    _('Filename "%s" out of filesystem root "%s"'),
                    $filename,
                    $this->_filepath
                )
            );
        }

        // Require valid $dirname root location
        if (!str_starts_with($dirname, $this->_filepath))
        {
            throw new \Exception(
                sprintf(
                    _('Dirname "%s" out of filesystem root "%s"'),
                    $dirname,
                    $this->_filepath
                )
            );
        }

        // Build path
        return str_repeat( // iterate ../ up to the --target location
            sprintf(
                '..%s',
                DIRECTORY_SEPARATOR
            ),
            substr_count(
                ltrim( // strip leading slash from $dirname
                    str_replace( // strip --target prefix from $dirname
                        $this->_filepath,
                        DIRECTORY_SEPARATOR,
                        $dirname
                    ),
                    DIRECTORY_SEPARATOR
                ),
                DIRECTORY_SEPARATOR
            ) + 1
        ) . ltrim( // strip leading slash from $filename
            str_replace( // strip --target prefix from $filename
                $this->_filepath,
                DIRECTORY_SEPARATOR,
                $filename
            ),
            DIRECTORY_SEPARATOR
        );
    }
}