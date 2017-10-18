<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Processor\GitHook;

use Sjorek\Composer\VirtualEnvironment\Processor\ExecutableFromTemplateTrait;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class StreamProcessor extends AbstractProcessor
{
    use ExecutableFromTemplateTrait;

    const PROCESSOR_NAME = 'git-hook stream';

    /**
     * @param string $name
     * @param string $url
     * @param string $baseDir
     * @param string $gitHookDir
     */
    public function __construct($name, $url, $baseDir, $gitHookDir = null)
    {
        parent::__construct($name, $url, $baseDir, $gitHookDir);
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Processor\GitHook\AbstractProcessor::deployHook()
     */
    protected function deployHook(OutputInterface $output, $force)
    {
        return $this->deployTemplate($output, $force);
    }

    /**
     * @param  OutputInterface $output
     * @param  string          $force
     * @return string|bool
     */
    protected function fetchTemplate(OutputInterface $output, $force = false)
    {
        $source = filter_var($this->source, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
        if ($source === false) {
            $output->writeln(
                sprintf(
                    '<error>Invalid url given for %s template %s.</error>',
                    static::PROCESSOR_NAME,
                    $this->source
                )
            );

            return false;
        }

        $scheme = parse_url($source, PHP_URL_SCHEME);
        if ($scheme === 'http' || $scheme === 'https') {
            $headers = @get_headers($source);
            if ($headers === false ||
                empty(
                    array_filter(
                        $headers,
                        function($header) { return strpos($header, '200 OK') !== false; }
                    )
                )
            ) {
                $output->writeln(
                    sprintf(
                        '<error>The %s template %s was not found.</error>',
                        static::PROCESSOR_NAME,
                        $this->source
                    )
                );

                return false;
            }
        } elseif (!file_exists($source)) {
            $output->writeln(
                sprintf(
                    '<error>The %s template %s does not exist.</error>',
                    static::PROCESSOR_NAME,
                    $this->source
                )
            );

            return false;
        }

        return @file_get_contents($source, false);
    }

    /**
     * {@inheritDoc}
     * @see \Sjorek\Composer\VirtualEnvironment\Processor\GitHook\AbstractProcessor::rollbackHook()
     */
    protected function rollbackHook(OutputInterface $output)
    {
        return $this->rollbackTemplate($output);
    }
}
