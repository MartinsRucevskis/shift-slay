<?php

declare(strict_types=1);

namespace App\Shift\Shifts;

//use App\Shift\FileMover\FixFile;
//use App\Shift\Objects\FileClass;
use App\Shift\Shifter\DepreciatedPackages;
use App\Shift\TypeDetector\FileAnalyzer;
use Exception;
use Symfony\Component\Process\Process;

class Lumen8ToLaravel8 implements BaseShift
{
    public function run(string $directory): void
    {
        $this->addLaravelFiles(app_path('/Shift/LaravelShiftFiles/Laravel8/'), $directory);
        $this->fixConfig($directory);
        $this->runRector($directory);
        if (config('shift.codeception')) {
            $this->fixCodeceptionConfig($directory);
        }
        $this->moveRoutes($directory);
    }

    private function addLaravelFiles(string $sourceDirectory, string $destinationDirectory): void
    {
        $filesAndDirectories = scandir($sourceDirectory) ?: throw new Exception('Failed to scan directory');
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
            } elseif (! file_exists($destinationPath) || str_contains($destinationPath, 'config/app.php') || str_contains($destinationPath, 'index.php')) {
                copy($sourcePath, $destinationPath);
            }
        }
    }

    private function fixConfig(string $directory): void
    {
        $appBoostrap = file_get_contents($directory.'/bootstrap/app.php') ?: throw new Exception(sprintf('Failed to read file: %s/bootstrap/app.php', $directory));
        $providers = $this->bootstrapProviders($appBoostrap);
        $middlewares = $this->bootstrapMiddlewares($appBoostrap);
        $aliases = $this->bootstrapAliases($appBoostrap);
        copy(app_path('/Shift/LaravelShiftFiles/Codeception/bootstrapAppCodeception.txt'), $directory.'/bootstrap/app.php');

        $configApp = file_get_contents($directory.'/config/app.php') ?: throw new Exception(sprintf('Failed to read file: %s/config/app.php', $directory));
        $configAliases = array_unique([...$this->configAliases($configApp), ...$aliases]);
        $configAliases = implode(PHP_EOL, $configAliases);

        $configApp = preg_replace('#\'aliases\' => \[([\s\S]*?)\]#m', "'aliases' => [".$configAliases.']', $configApp);
        file_put_contents($directory.'/config/app.php', $configApp);

        $configApp = file_get_contents($directory.'/config/app.php') ?: throw new Exception(sprintf('Failed to read file: %s/config/app.php', $directory));
        $configProviders = $this->configProviders($configApp);

        $configProviders = $this->uniqueProviders($providers, $configProviders);
        $configProviders = implode(PHP_EOL, $configProviders);

        $configApp = preg_replace('#\'providers\' => \[([\s\S]*?)\]#m', "'providers' => [".$configProviders.']', $configApp);
        file_put_contents($directory.'/config/app.php', $configApp);

        $kernel = file_get_contents($directory.'/app/Http/Kernel.php') ?: throw new Exception(sprintf('Failed to read file: %s/app/Http/Kernel.php', $directory));
        $groups = array_filter($middlewares, static fn (&$middleware): bool => str_contains($middleware, '=>'));
        $nonGroups = array_filter($middlewares, static fn ($middleware): bool => ! str_contains($middleware, '=>'));
        $kernelMiddlewares = $this->kernelMiddlewares($kernel);
        foreach ($kernelMiddlewares as $kernelMiddleware) {
            foreach ($middlewares as $key => $providerName) {
                $middlewareClass = preg_replace('/\s+/', '', (string) $kernelMiddleware);
                if ($providerName === $middlewareClass) {
                    unset($middlewares[$key]);
                }
            }
        }

        $middlewares = [...$kernelMiddlewares, ...$nonGroups];
        $middlewares = implode(PHP_EOL, $middlewares);

        $kernel = preg_replace('#\\$middleware = \[([\s\S]*?)\]#m', '$middleware = ['.$middlewares.']', $kernel);
        file_put_contents($directory.'/app/Http/Kernel.php', $kernel);

        $kernel = file_get_contents($directory.'/app/Http/Kernel.php') ?: throw new Exception('Couldnt open Kernel');
        $groups = $this->splitMiddlewareGroups($groups);
        file_put_contents($directory.'/app/Http/Kernel.php', $this->fixKernelMiddlewareGroups($kernel, $groups));

    }

    /**
     * @param  string[]  $array
     * @return array<int, string>
     */
    private function splitBootstrapArray(array $array): array
    {
        $items = [];
        foreach ($array as $item) {
            if ($item[0] === '[' && $item[strlen((string) $item) - 1]) {
                $item = substr((string) $item, 1, -1);
                $item = preg_replace('#\s+#', '', $item) ?? '';
                $stringAsArray = explode(',', $item);
                if (end($stringAsArray) === '') {
                    array_pop($stringAsArray);
                }

                $items[] = $stringAsArray;
            }
        }

        return collect($items === [] ? $array : $items)->flatten()->toArray();
    }

    /**
     * @return string[]
     */
    private function bootstrapMiddlewares(string $bootstrap): array
    {
        preg_match_all('#\$app->middleware\(([=>\s+\',\[\]A-za-z\\:]*)\);#m', $bootstrap, $middlewares);

        $middlewares = $this->splitBootstrapArray($middlewares[1]);
        foreach ($middlewares as &$middleware) {
            $middleware .= ',';
        }

        return $middlewares;
    }

    /**
     * @return string[]
     */
    private function bootstrapProviders(string $bootstrap): array
    {
        preg_match_all('#\$app->register\(([A-za-z\\:]*)\);#m', $bootstrap, $providers);
        $providers = $providers[1];
        $bootstrapImports = (new FileAnalyzer($bootstrap))->useStatements();
        foreach ($providers as &$provider) {
            if (! str_contains((string) $provider, '\\') && isset($bootstrapImports[str_replace('::class', '', (string) $provider)])) {
                $provider = $bootstrapImports[str_replace('::class', '', (string) $provider)];
            }

            if (! str_contains((string) $provider, '::class')) {
                $provider .= '::class';
            }

            $provider .= ',';
        }

        return $providers;
    }

    /**
     * @return array<int, string>
     */
    private function bootstrapAliases(string $bootstrap): array
    {
        preg_match_all('#\$app->alias\(([=>\s+\',\[\]A-za-z\\:]*)\);#m', $bootstrap, $aliases);
        $aliases = $aliases[1];
        foreach ($aliases as &$alias) {
            $alias = str_replace(['"', "'"], '', (string) $alias);
            if (! str_contains($alias, '::class')) {
                $alias .= '::class';
            }

            $alias = str_replace(',', ' =>', $alias);
            $alias = preg_replace('#(.*?)\s+=>#', '\'$1\' =>', $alias);
            $alias .= ',';
        }

        return $aliases;
    }

    /**
     * @return array<int, string>
     */
    private function configAliases(string $config): array
    {
        preg_match('#\'aliases\' => \[([\s\S]*?)\]#m', $config, $configAliases);

        return explode(PHP_EOL, $configAliases[1]);
    }

    /**
     * @return array<int, string>
     */
    private function configProviders(string $config): array
    {
        preg_match('#\'providers\' => \[([\s\S]*?)\]#m', $config, $configProviders);

        return preg_split('/\r\n|\n|\r/', $configProviders[1]) ?: [];
    }

    /**
     * @param  string[]  $providers
     * @param  array<int, string>  $configProviders
     * @return array<int, string>
     */
    private function uniqueProviders(array $providers, array $configProviders): array
    {
        foreach ($configProviders as $configProvider) {
            foreach ($providers as $key => $providerName) {
                $configClass = preg_replace('/\s+/', '', (string) $configProvider);
                if ($providerName === $configClass || (new DepreciatedPackages($providerName))->isDepreciated()) {
                    unset($providers[$key]);
                }
            }
        }

        return [...$configProviders, ...$providers];
    }

    /**
     * @return array<int, string>
     */
    private function kernelMiddlewares(string $kernel): array
    {
        preg_match('#\\$middleware = \[([\s\S]*?)\]#m', $kernel, $kernelMiddlewares);

        return preg_split('/\r\n|\n|\r/', $kernelMiddlewares[1]) ?: [];
    }

    /**
     * @param  array<string, string>  $groups
     * @return array<string, string[]|string>
     */
    private function splitMiddlewareGroups(array $groups): array
    {
        $middlewares = [];
        array_map(static function ($middleware) use (&$middlewares): void {
            $splitMiddleware = explode('=>', $middleware);
            $splitMiddleware[0] = preg_replace('/\s+/', '', $splitMiddleware[0]) ?? $splitMiddleware[0];
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

    /**
     * @param  array<string, string[]|string>  $groups
     */
    private function fixKernelMiddlewareGroups(string $kernel, array $groups): string
    {
        preg_match('/\$middlewareGroups = \[([\s\S]+?)\];/', $kernel, $matches);
        $middlewareGroups = $matches[1];
        foreach ($groups as $groupName => $classes) {
            preg_match("/'".preg_quote($groupName).'\' => \[([\s+\S+]+?)\]/ms', $middlewareGroups, $matches);
            $middlewares = preg_split('/\n/', $matches[1]) ?: [];
            foreach ($middlewares as $key => $middleware) {
                $middlewareClass = preg_replace('/\s+/', '', $middleware);
                if (in_array($middlewareClass, (is_array($classes) ? $classes : [$classes]))) {
                    unset($middlewares[$key]);
                }
            }

            $newGroupMiddlewares = array_merge($middlewares, (is_array($classes) ? $classes : [$classes]));
            $kernel = preg_replace(
                "/('".preg_quote($groupName).'\' => \[)([\s+\S+]+?)\]/ms',
                '$1'.implode(PHP_EOL, $newGroupMiddlewares).']',
                $kernel
            );
        }

        return $kernel ?? '';
    }

    private function fixCodeceptionConfig(string $directory): void
    {
        file_put_contents($directory.'/tests/_bootstrap.php', file_get_contents(app_path('Shift/LaravelShiftFiles/Codeception/testSuiteBootstrap.txt')));
    }

    private function runRector(string $directory): void
    {
        $directories = scandir($directory);
        if (! $directories) {
            throw new \Exception('Couldn\'t open the specified directory : '.$directory);
        }
        $directories = array_filter($directories, fn ($dir) => ! str_contains($dir, '.') && ! str_contains($dir, 'vendor') && is_dir($dir));
        foreach ($directories as &$dir) {
            $dir = $directory.'\\'.$dir;
        }
        unset($dir);
        $process = new Process(['vendor/bin/rector', 'process', ...$directories, '--config', app_path('\Shift\Rector\Lumen8ToLaravel8\rector.php')], null, null, null, 160);
        $process->run();
        echo $process->getOutput();
    }

    private function moveRoutes(string $directory): void
    {
        $routes = file_get_contents($directory.'/routes/web.php');
        file_put_contents($directory.'/routes/api.php', $routes);
        file_put_contents($directory.'/routes/web.php', '');
    }
}
