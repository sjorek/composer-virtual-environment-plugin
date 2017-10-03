<?php
namespace Sjorek\Composer;

use Composer\Command\BaseCommand;
use Composer\Factory;
use Composer\Json\JsonFile;
use Composer\Util\Filesystem;
use Composer\Util\Silencer;
use Composer\Util\Platform;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class VirtualEnvironmentCommand extends BaseCommand
{
    protected function configure()
    {
        $io = $this->getIO();
        $recipe = Factory::getComposerFile();
        $json = new JsonFile($recipe, null, $io);
        $manifest = $json->read();

        $composer = realpath($_SERVER['argv'][0]) ?: null;
        $php = exec('which php') ?: null;

        $this
            ->setName('virtual-environment')
            ->setDescription('Setup a virtual environment.')
            ->setDefinition(array(
                new InputOption('name', null, InputOption::VALUE_REQUIRED, 'Name of the virtual environment', $manifest['name']),
                new InputOption('php', null, InputOption::VALUE_OPTIONAL, 'Add symlink to php', $php),
                new InputOption('composer', null, InputOption::VALUE_OPTIONAL, 'Add symlink to composer', $composer),
                new InputOption('force', "f", InputOption::VALUE_OPTIONAL, 'Force overwriting existing environment scripts', false)
            ))
            ->setHelp(<<<EOT
The <info>virtual-environment</info> command creates files
to activate/deactivate the current bin directory in shell,
optionally placing a symlink to the current php-binary.

<info>php composer.phar virtual-environment</info>

After this you can source the activation-script
corresponding to your shell:

bash/zsh:

    <info>$ source bin/activate</info>

csh:

    <info>$ source bin/activate.csh</info>

fish:

    <info>$ . bin/activate.fish</info>

bash (alternative):

    <info>$ source bin/activate.bash</info>

zsh (alternative):

    <info>$ source bin/activate.zsh</info>

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $composer = $this->getComposer();
        $config = $composer->getConfig();
        $recipe = Factory::getComposerFile();
        $io = $this->getIO();

        $filesystem = new Filesystem();
        $basePath = $filesystem->normalizePath(realpath(realpath(dirname($recipe))));
        $binPath = $filesystem->normalizePath($config->get('bin-dir'));
        $resPath = $filesystem->normalizePath(__DIR__ . '/../res');

        $json = new JsonFile($recipe, null, $io);
        $manifest = $json->read();

        if ($input->getOption('name')) {
            $name = $input->getOption('name');
        } else {
            $name = $manifest['name'];
        }

        $templates = array(
            'activate',
            'activate.bash',
            'activate.csh',
            'activate.fish',
            'activate.zsh'
        );
        foreach($templates as $template) {
            $source = $resPath . '/' .$template;
            $target = $binPath . '/' .$template;
            if (file_exists($target) && !$input->getArgument('force')) {
                $io->writeError('    <warning>Skipped installation of bin '.$target.': file already exists</warning>');
                continue;
            }
            $data = file_get_contents($source, false);
            $data = str_replace(
                array(
                    '@NAME@',
                    '@BASE_DIR@',
                    '@BIN_DIR@'
                ), array(
                    $name,
                    $basePath,
                    $binPath
                ),
                $data
            );
            $filesystem->ensureDirectoryExists($config->get('bin-dir'));
            file_put_contents($target, $data);
            Silencer::call('chmod', $target, 0777 & ~umask());

            $output->writeln('Installed virtual environment script: ' . $target);
        }

        $symlinks = array();
        if ($input->getOption('php')) {
            $symlinks['php'] = realpath($input->getOption('php')) ?: $input->getOption('php');
        }
        if ($input->getOption('composer')) {
            $symlinks['composer'] = realpath($input->getOption('composer')) ?: $input->getOption('composer');
        }
        if (!empty($symlinks) && Platform::isWindows()) {
            $io->writeError('    <warning>Skipped creation of symbolic links on windows</warning>');
            return ;
        }
        foreach($symlinks as $name => $source) {
            $target = $binPath . '/' .$name;
            if (file_exists($target) || is_link($target)) {
                if ($input->getArgument('force')) {
                    if (!$filesystem->unlink($target)) {
                        $io->writeError('    <warning>Skipped creation of symbolic link '.$target.': force-option given, while file already exists and its removal failed</warning>');
                        continue;
                    }
                } else {
                    $io->writeError('    <warning>Skipped creation of symbolic link '.$target.': file already exists</warning>');
                    continue;
                }
            }
            if (!(file_exists($source) || is_link($target))) {
                $io->writeError('    <warning>Skipped creation of symbolic link '.$target.': ' . $source . ' does not exist</warning>');
                continue;
            }
            $filesystem->ensureDirectoryExists($config->get('bin-dir'));
            if (!$filesystem->relativeSymlink($source, $target)) {
                $io->writeError('    <warning>Creation of symbolic link '.$target.' -> ' . $source . ' failed</warning>');
                continue;
            }
            $output->writeln('Installed virtual environment symlink: ' . $target .' -> ' . $source );
        }
    }
}
