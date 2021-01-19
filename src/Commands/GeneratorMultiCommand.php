<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Exceptions\FileAlreadyExistException;
use Nwidart\Modules\Generators\FileGenerator;

abstract class GeneratorMultiCommand extends Command
{
    /**
     * The name of 'name' argument.
     *
     * @var string
     */
    protected $argumentName = '';

    /**
     * Single or multi file stubs need generate.
     *
     * @var []
     */
    protected $multiFiles = [];

    /**
     * Get multi template contents.
     *
     * @return string
     */
    abstract protected function getMultiTemplateContents($fileName = null);

    /**
     * Get the destination file path.
     *
     * @return string
     */
    abstract protected function getMultiDestinationFilePath($fileName = null);

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = str_replace('\\', '/', $this->getMultiDestinationFilePath());

        if (!$this->laravel['files']->isDirectory($dir = dirname($path))) {
            $this->laravel['files']->makeDirectory($dir, 0777, true);
        }

        if (empty($this->multiFiles)) {

            $contents = $this->getMultiTemplateContents();

            return $this->fileGenerate($path, $contents);
        } else {
            if (!empty($this->argument('name'))) {
                $this->multiFiles = [$this->argument('name')];
            }
            foreach ($this->multiFiles as $file_name) {

                $path = str_replace('\\', '/', $this->getMultiDestinationFilePath($file_name));

                $contents = $this->getMultiTemplateContents($file_name);

                if ($this->fileGenerate($path, $contents) == E_ERROR)
                    return E_ERROR;
            }
        }

        return 0;
    }

    /**
     * File generate
     */
    public function fileGenerate($path, $contents)
    {
        try {
            $overwriteFile = $this->hasOption('force') ? $this->option('force') : false;
            (new FileGenerator($path, $contents))->withFileOverwrite($overwriteFile)->generate();

            $this->info("Created : {$path}");
        } catch (FileAlreadyExistException $e) {
            $this->error("File : {$path} already exists.");

            return E_ERROR;
        }

        return 0;
    }

    /**
     * Get class name.
     *
     * @return string
     */
    public function getClass()
    {
        return class_basename($this->argument($this->argumentName));
    }

    /**
     * Get default namespace.
     *
     * @return string
     */
    public function getDefaultNamespace(): string
    {
        return '';
    }

    /**
     * Get class namespace.
     *
     * @param \Nwidart\Modules\Module $module
     *
     * @return string
     */
    public function getClassNamespace($module)
    {
        $extra = str_replace($this->getClass(), '', $this->argument($this->argumentName));

        $extra = str_replace('/', '\\', $extra);

        $namespace = $this->laravel['modules']->config('namespace');

        $namespace .= '\\' . $module->getStudlyName();

        $namespace .= '\\' . $this->getDefaultNamespace();

        $namespace .= '\\' . $extra;

        $namespace = str_replace('/', '\\', $namespace);

        return trim($namespace, '\\');
    }
}
