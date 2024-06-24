# gemini-dl

CLI downloader for [Gemini protocol](https://geminiprotocol.net), inspired by `wget` and `yt-dlp` tools

At this moment, project under development, some [features](#features) already implemented and testing ([feedback](https://github.com/YGGverse/gemini-dl/issues) | [PR](https://github.com/YGGverse/gemini-dl/pull))

## Interface

## Components

* [gemini-php](https://github.com/YGGverse/gemini-php) - Client for Gemini protocol
* [gemtext-php](https://github.com/YGGverse/gemtext-php) - Parser for Gemtext
* [net-php](https://github.com/YGGverse/net-php) - Network toolkit for URL operations
* [php-cli-colors](https://github.com/mikeerickson/php-cli-colors) - CLI colors

## Features

* [x] Grab single location or entire capsule for mirroring or offline reading
* [x] Multiple MIME types download support (e.g. inline images and other media)
* [x] Detailed crawler log for every request
* [ ] Flexible options
  * [x] Custom delay between requests
  * [x] Custom index filename for directories
  * [x] Custom storage location
    * [x] Filesystem
    * [ ] FTP
  * [x] Optional URL replacement to relative URI
  * [x] Recursive crawler
  * [x] Regex URL rules
  * [x] Unique snap version or sync local copy (by default)
  * [ ] Configurable redirect levels to follow
  * [ ] Crawl depth levels limit
  * [ ] Document size limit to download
  * [ ] Follow external links on crawl

## Environment

``` bash
apt install git composer php-fpm php-mbstring
```

## Install

* `git clone`
* `cd gemini-dl`
* `composer update`
* `chmod +x src/gemini-dl.php` _(for direct execution only)_

## Usage

``` bash
src/gemini-dl.php --source gemini://.. --target /path/to/download
```

* alternatively, launch with specified php version `/path/to/php src/gemini-dl.php`

### Options

``` bash
## Required

  -s, --source   - string, gemini protocol address
  -t, --target   - string, absolute path to destination folder

## Optional

  -c, --crawl    - no value, crawl document links (entire capsule download), disabled by default
  -d, --delay    - integer, pause between requests (seconds), 1 by default
  -i, --index    - string, index filename of directory listing, index.gmi by default
  -h, --help     - no value, show available commands
  -k, --keep     - no value, keep original links (--crawl mode only), disabled by default
  -m, --match    - string, collect links match regex rule, /.*/ by default
  -r, --raw      - no value, include meta headers (--keep option ignored), disabled by default
  -u, --unique   - no value, append snap version as folder timestamp, disabled by default

## Experimental (in development)

  -e, --external - no value, follow external hosts, disabled by default
  -f, --follow   - integer, follow redirects on --crawl, 0 by default
  -l, --level    - integer, depth to --crawl, 0 by default
```

* show in CLI: `gemini-dl.php --help`