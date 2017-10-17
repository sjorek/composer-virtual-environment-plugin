<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Processor;

use Composer\Util\Filesystem;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class GitHookProcessor
{
    protected $source;
    protected $target;
    protected $baseDir;
    protected $filesystem;

    /**
     * @param string $source
     * @param string $target
     * @param string $baseDir
     */
    public function __construct($source, $target, $baseDir)
    {
        $this->source = $source;
        $this->target = $target;
        $this->baseDir = $baseDir;
        $this->filesystem = new Filesystem();
    }

    /**
     * @param  OutputInterface $output
     * @param  bool            $force
     * @return bool
     */
    public function deploy(OutputInterface $output, $force = false)
    {
//         $source = $this->source;
//         if (!$this->filesystem->isAbsolutePath($source)) {
//             $source = $this->baseDir . '/' . $source;
//         }
//         $target = $this->target;
//         if (!$this->filesystem->isAbsolutePath($target)) {
//             $target = dirname($source) . '/' . $target;
//         }

//         if ($source === $target) {
//             $output->writeln(
//                 sprintf(
//                     '<error>Skipped creation of symbolic link, as source %s and target %s are the same.</error>',
//                     $this->source,
//                     $this->target
//                 )
//             );

//             return false;
//         }
//         if (file_exists($source) || is_link($source)) {
//             if ($force) {
//                 try {
//                     if ($this->filesystem->unlink($source)) {
//                         $output->writeln(
//                             sprintf(
//                                 '<comment>Removed existing file for symbolic link %s.</comment>',
//                                 $this->source
//                             ),
//                             OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
//                         );
//                     }
//                 } catch (\RuntimeException $e) {
//                     $output->writeln(
//                         sprintf(
//                             '<error>Could not remove existing symbolic link %s: %s</error>',
//                             $this->source,
//                             $e->getMessage()
//                         )
//                     );

//                     return false;
//                 }
//             } else {
//                 $output->writeln(
//                     sprintf(
//                         '<error>Skipped creation of symbolic link, as the source %s already exists.</error>',
//                         $this->source
//                     )
//                 );

//                 return false;
//             }
//         }
//         if (!(file_exists($target) || is_link($target))) {
//             $output->writeln(
//                 sprintf(
//                     '<error>Skipped creation of symbolic link, as the target %s does not exist.</error>',
//                     $this->target
//                 )
//             );

//             return false;
//         }
//         if (dirname($source)) {
//             try {
//                 $this->filesystem->ensureDirectoryExists(dirname($source));
//             } catch (\RuntimeException $e) {
//                 $output->writeln(
//                     sprintf(
//                         '<error>Failed to create the symlink directory %s: %s</error>',
//                         dirname($source),
//                         $e->getMessage()
//                     )
//                 );

//                 return false;
//             }
//         }
//         // special treatment for relative symlinks in the same directory,
//         // because composer's implementation uses a leading dot (./...)
//         if (strpos($this->target, '/') === false && symlink($this->target, $source)) {
//             $output->writeln(
//                 sprintf(
//                     '<comment>Installed symbolic link %s to target %s.</comment>',
//                     $this->source,
//                     $this->target
//                 ),
//                 OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
//             );

//             return true;
//         } elseif (strpos($this->target, '/') !== false && $this->filesystem->relativeSymlink($target, $source)) {
//             $output->writeln(
//                 sprintf(
//                     '<comment>Installed symbolic link %s to target %s.</comment>',
//                     $this->source,
//                     $this->target
//                 ),
//                 OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
//             );

//             return true;
//         } else {
//             $output->writeln(
//                 sprintf(
//                     '<error>Creation of symbolic link failed for source %s and target %s.</error>',
//                     $this->source,
//                     $this->target
//                 )
//             );
//         }

        return false;
    }

    /**
     * @param  OutputInterface $output
     * @return bool
     */
    public function rollback(OutputInterface $output)
    {
//         $source = $this->source;
//         if (!$this->filesystem->isAbsolutePath($source)) {
//             $source = $this->baseDir . '/' . $source;
//         }
//         // Attention: Dangling symlinks return false for is_link(), hence we have to use file_exists()!
//         if (file_exists($source) || is_link($source)) {
//             try {
//                 if ($this->filesystem->unlink($source)) {
//                     $output->writeln(
//                         sprintf(
//                             '<comment>Removed symbolic link %s.</comment>',
//                             $this->source
//                         ),
//                         OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
//                     );

//                     return true;
//                 }
//             } catch (\RuntimeException $e) {
//                 $output->writeln(
//                     sprintf(
//                         '<error>Could not remove symbolic link %s: %s</error>',
//                         $this->source,
//                         $e->getMessage()
//                     )
//                 );

//                 return false;
//             }
//         } else {
//             $output->writeln(
//                 sprintf(
//                     '<comment>Skipped removing symbolic link, as %s does not exist.</comment>',
//                     $this->source
//                 ),
//                 OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE
//             );

//             return true;
//         }

        return false;
    }
}