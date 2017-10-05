<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephnan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sjorek\Composer\VirtualEnvironment\Config;

use Composer\Factory;

/**
 * @author Stephan Jorek <stephan.jorek@gmail.com>
 */
class LocalConfiguration extends AbstractConfiguration
{
    protected function getRecipeFilename()
    {
        $recipe = Factory::getComposerFile();

        return dirname($recipe) . DIRECTORY_SEPARATOR . basename($recipe, '.json') . '.venv';
    }
}
