<?php
namespace Sjorek\Composer\ShellPlugin\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Composer\Factory;
use Composer\Json\JsonFile;
use Composer\Util\Filesystem;
use Composer\Util\Silencer;

/**
 *
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class VirtualEnvironmentCommand extends BaseCommand
{
    protected function configure()
    {
        $file = Factory::getComposerFile();
        $json = new JsonFile($file, null, $io);
        $manifest = $json->read();

        $this
            ->setName('virtual-environment')
            ->setDescription('Setup a virtual environment.')
            ->setDefinition(array(
                new InputOption('name', "n", InputOption::VALUE_REQUIRED, 'Name of the virtual environment', $manifest['name']),
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
        $io = $this->getIO();
        $composer = $this->getComposer();
        $config = $composer->getConfig();

        $recipe = Factory::getComposerFile();
        $json = new JsonFile($basePath . $recipe, null, $io);
        $manifest = $json->read();

        if ($input->getArgument('name')) {
            $name = $input->getArgument('name');
        } else {
            $name = $manifest['name'];
        }

        $filesystem = new Filesystem();
        $filesystem->ensureDirectoryExists($config->get('bin-dir'));
        // Do not remove double realpath() calls.
        // Fixes failing Windows realpath() implementation.
        // See https://bugs.php.net/bug.php?id=72738
        $basePath = $filesystem->normalizePath(realpath(realpath(dirname($recipe))));
        $binPath = $filesystem->normalizePath(realpath(realpath($config->get('bin-dir'))));
        $resPath = $filesystem->normalizePath(realpath(realpath(__DIR__ . '/../../../../res')));

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
            file_put_contents($target, $data);
            Silencer::call('chmod', $target, 0777 & ~umask());

            $output->writeln('Installed virtual environment script: ' . $target);
        }
    }
}
