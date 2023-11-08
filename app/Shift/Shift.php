<?php

namespace App\Shift;

use App\Shift\FileMover\FixFile;
use App\Shift\Objects\FileClass;
use App\Shift\Shifter\DepreciatedPackages;
use App\Shift\TypeDetector\FileAnalyzer;

class Shift
{
    /**
     * @var string[]
     */

    private array $filesToOverwrite = [
        'web/tests/_bootstrap.php',
        'web/artisan',
        'web/public/index.php',
    ];

    public function run(string $directory): void
    {
        //        $this->addLaravelFiles('C:\Users\martins.rucevskis\plainLaravel8', $directory);
        $this->fixConfig($directory);
        $this->fixFiles($directory);
        $this->fixCodeceptionConfig($directory);
    }

    private function addLaravelFiles(string $sourceDirectory, string $destinationDirectory): void
    {
        $filesAndDirectories = scandir($sourceDirectory);

        unset($filesAndDirectories[array_search('.', $filesAndDirectories, true)]);
        unset($filesAndDirectories[array_search('..', $filesAndDirectories, true)]);

        if (count($filesAndDirectories) < 1) {
            return;
        }

        foreach ($filesAndDirectories as $fileOrDirectory) {
            $sourcePath = $sourceDirectory.'/'.$fileOrDirectory;
            $destinationPath = $destinationDirectory.'/'.$fileOrDirectory;

            if (is_dir($sourcePath)) {
                if (! is_dir($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $this->addLaravelFiles($sourcePath, $destinationPath);
            } elseif (! file_exists($destinationPath) || str_contains($destinationPath, 'config/app.php')) {
                copy($sourcePath, $destinationPath);
            } elseif (file_exists($destinationPath)) {
                $this->overLappingFiles[] = $destinationPath;
            }
        }
    }

    private function fixFiles(string $directory): void
    {
        $filesAndDirectories = scandir($directory);

        unset($filesAndDirectories[array_search('.', $filesAndDirectories, true)]);
        unset($filesAndDirectories[array_search('..', $filesAndDirectories, true)]);

        if (count($filesAndDirectories) < 1) {
            return;
        }

        foreach ($filesAndDirectories as $fileOrDirectory) {
            $fullPath = $directory.'/'.$fileOrDirectory;
            if (is_dir($fullPath)) {
                if (str_contains($fileOrDirectory, 'vendor')) {
                    continue;
                }
                $this->fixFiles($fullPath);
            } elseif (str_contains($fileOrDirectory, '.php') && ! str_contains($fileOrDirectory, 'autoload.php')) {
                try {
                    (new FixFile(
                        new FileClass($fullPath)
                    ))->fix();
                } catch (\Exception $exception) {
                    echo $exception->getMessage().PHP_EOL;
                }
            }
        }
    }

    private function fixConfig(string $directory): void
    {
        $appBoostrap = file_get_contents("{$directory}/bootstrap/app.php");

        $providers = $this->bootstrapProviders($appBoostrap);
        $middlewares = $this->bootstrapMiddlewares($appBoostrap);
        $aliases = $this->bootstrapAliases($appBoostrap);

        copy(__DIR__.'/Laravel8ShiftFiles/bootstrapAppCodeception.txt', "{$directory}/bootstrap/app.php");

        $configApp = file_get_contents("{$directory}/config/app.php");
        $configAliases = array_unique(array_merge($this->configAliases($configApp), $aliases));
        $configAliases = implode(PHP_EOL, $configAliases);

        $configApp = preg_replace('#\'aliases\' => \[([\s\S]*?)\]#m', '\'aliases\' => ['.$configAliases.']', $configApp);
        file_put_contents("{$directory}/config/app.php", $configApp);

        $configApp = file_get_contents("{$directory}/config/app.php");
        $configProviders = $this->configProviders($configApp);

        $configProviders = $this->uniqueProviders($providers, $configProviders);
        $configProviders = implode(PHP_EOL, $configProviders);
        $configApp = preg_replace('#\'providers\' => \[([\s\S]*?)\]#m', '\'providers\' => ['.$configProviders.']', $configApp);
        file_put_contents("{$directory}/config/app.php", $configApp);

        $kernel = file_get_contents("{$directory}/app/Http/Kernel.php");

        $groups = array_filter($middlewares, function (&$middleware) {
            return str_contains($middleware, '=>');
        });
        $nonGroups = array_filter($middlewares, function ($middleware) {
            return ! str_contains($middleware, '=>');
        });
        $kernelMiddlewares = $this->kernelMiddlewares($kernel);
        foreach ($kernelMiddlewares as $kernelMiddleware) {
            foreach ($middlewares as $key => $providerName) {
                $middlewareClass = preg_replace('/\s+/', '', $kernelMiddleware);
                if ($providerName === $middlewareClass) {
                    unset($middlewares[$key]);
                }
            }
        }
        $middlewares = array_merge($kernelMiddlewares, $nonGroups);
        $middlewares = implode(PHP_EOL, $middlewares);
        $kernel = preg_replace('#\\$middleware = \[([\s\S]*?)\]#m', '$middleware = ['.$middlewares.']', $kernel);
        file_put_contents("{$directory}/app/Http/Kernel.php", $kernel);

        $groups = $this->splitMiddlewareGroups($groups);
        file_put_contents("{$directory}/app/Http/Kernel.php", $this->fixKernelMiddlewareGroups($kernel, $groups));

    }

    private function splitBootstrapArray(array $array): array
    {
        $items = [];
        foreach ($array as $item) {
            if ($item[0] === '[' && $item[strlen($item) - 1]) {
                $item = substr($item, 1, -1);
                $item = preg_replace('#\s+#', '', $item);
                $stringAsArray = explode(',', $item);
                if (end($stringAsArray) === '') {
                    array_pop($stringAsArray);
                }
                $items[] = $stringAsArray;
            }
        }

        return $items === [] ? $array : $items;
    }

    /**
     * @return string[]
     */
    private function bootstrapMiddlewares(string $bootstrap): array
    {
        preg_match_all('#\$app->middleware\(([=>\s+\',\[\]A-za-z\\:]*)\);#m', $bootstrap, $middlewares);

        $middlewares = $this->splitBootstrapArray($middlewares[1]);
        foreach ($middlewares[0] as &$middleware) {
            $middleware .= ',';
        }

        return $middlewares[0];
    }

    private function bootstrapProviders(string $bootstrap): array
    {
        preg_match_all('#\$app->register\(([A-za-z\\:]*)\);#m', $bootstrap, $providers);
        $providers = $providers[1];
        $bootstrapImports = (new FileAnalyzer($bootstrap))->useStatements();
        foreach ($providers as &$provider) {
            if (! str_contains($provider, '\\')) {
                if (isset($bootstrapImports[str_replace('::class', '', $provider)])) {
                    $provider = $bootstrapImports[str_replace('::class', '', $provider)];
                }
            }
            if (! str_contains($provider, '::class')) {
                $provider .= '::class';
            }
            $provider .= ',';
        }

        return $providers;
    }

    private function bootstrapAliases(string $bootstrap): array
    {
        preg_match_all('#\$app->alias\(([=>\s+\',\[\]A-za-z\\:]*)\);#m', $bootstrap, $aliases);
        $aliases = $aliases[1];
        foreach ($aliases as &$alias) {
            $alias = str_replace(['"', '\''], '', $alias);
            if (! str_contains($alias, '::class')) {
                $alias .= '::class';
            }
            $alias = str_replace(',', ' =>', $alias);
            $alias = preg_replace('#(.*?)\s+=>#', '\'$1\' =>', $alias);
            $alias .= ',';
        }

        return $aliases;
    }

    private function configAliases(string $config): array
    {
        preg_match('#\'aliases\' => \[([\s\S]*?)\]#m', $config, $configAliases);

        return explode(PHP_EOL, $configAliases[1]);
    }

    private function configProviders(string $config): array
    {
        preg_match('#\'providers\' => \[([\s\S]*?)\]#m', $config, $configProviders);

        return preg_split('/\r\n|\n|\r/', $configProviders[1]);
    }

    private function uniqueProviders(array $providers, array $configProviders): array
    {
        foreach ($configProviders as $configProvider) {
            foreach ($providers as $key => $providerName) {
                $configClass = preg_replace('/\s+/', '', $configProvider);
                if ($providerName === $configClass || (new DepreciatedPackages($providerName))->isDepreciated()) {
                    unset($providers[$key]);
                }
            }
        }

        return array_merge($configProviders, $providers);
    }

    private function kernelMiddlewares(string $kernel): array
    {
        preg_match('#\\$middleware = \[([\s\S]*?)\]#m', $kernel, $kernelMiddlewares);

        return preg_split('/\r\n|\n|\r/', $kernelMiddlewares[1]);
    }

    /**
     * @return array<string, string[]|string>
     */
    private function splitMiddlewareGroups(array $groups): array
    {
        $middlewares = [];
        array_map(function ($middleware) use (&$middlewares) {
            $splitMiddleware = explode('=>', $middleware);
            $splitMiddleware[0] = preg_replace('/\s+/', '', $splitMiddleware[0]);
            $splitMiddleware[0] = trim($splitMiddleware[0], '\'"');
            if ($splitMiddleware[1][0] !== '\\') {
                $splitMiddleware[1] = '\\'.$splitMiddleware[1];
            }
            $middlewares[$splitMiddleware[0]] = (isset($middlewares[$splitMiddleware[0]])
                ? array_merge($middlewares[$splitMiddleware[0]], [$splitMiddleware[1]])
                : $splitMiddleware[1]);

        }, $groups);

        return $middlewares;
    }

    private function fixKernelMiddlewareGroups(string $kernel, array $groups): string
    {
        preg_match('/\$middlewareGroups = \[([\s\S]+?)\];/', $kernel, $matches);
        $middlewareGroups = $matches[1];
        foreach ($groups as $groupName => $classes) {
            preg_match('/\''.preg_quote($groupName).'\' => \[([\s+\S+]+?)\]/ms', $middlewareGroups, $matches);
            $middlewares = preg_split('/\n/', $matches[1]);
            foreach ($middlewares as $key => $middleware) {
                $middlewareClass = preg_replace('/\s+/', '', $middleware);
                if (in_array($middlewareClass, (is_array($classes) ? $classes : [$classes]))) {
                    unset($middlewares[$key]);
                }
            }
            $newGroupMiddlewares = array_merge($middlewares, (is_array($classes) ? $classes : [$classes]));
            $kernel = preg_replace('/(\''.preg_quote($groupName).'\' => \[)([\s+\S+]+?)\]/ms', '$1'.implode(PHP_EOL, $newGroupMiddlewares).']', $kernel);
        }

        return $kernel;
    }

    private function fixCodeceptionConfig(string $directory): void
    {
        file_put_contents($directory.'/tests/_bootstrap.php', file_get_contents(app_path('Shift/Laravel8ShiftFiles/testSuiteBootstrap.txt')));
    }
}
