<?php

namespace Simp\Modal\ModalDefinitions;

use ReflectionException;

/**
 * Defines the configuration settings for a modal, including its directory path.
 *
 * This class provides functionality to initialize and store the modal directory
 * used for modal-related operations.
 *
 * @property string $modal_directory The path to the modal directory.
 */
final class ModalConfiguration
{
    /**
     * Where the modal directory is located.
     * @var string
     */
    protected string $modal_directory;

    /**
     * Constructor method.
     *
     * @param string $modal_directory The directory path for storing modal classes.
     * @return void
     */
    public function __construct(string $modal_directory)
    {
        $this->modal_directory = $modal_directory;
    }

    /**
     * Retrieves an array of modal objects by scanning the specified modal directory,
     * including and instantiating the corresponding classes for each file found.
     *
     * @return array An array of instantiated modal objects. Returns an empty array if no valid files are found.
     * @throws ReflectionException
     */
    public function getModals(): array
    {
        $files = array_diff(scandir($this->modal_directory), array('..', '.'));
        if (empty($files)) {
            return [];
        }

        $modals = [];
        foreach ($files as $file) {

            // full path to the file
            $filePath = $this->modal_directory . DIRECTORY_SEPARATOR . $file;
            if (is_file($filePath)) {
                $className = require_once $filePath;
                if (class_exists($className)) {
                    $reflection = new \ReflectionClass($className);
                    $modals[] = $reflection->newInstance();
                }
                else {
                    $fullClassName = $this->getFullClassNameFromFile($filePath);
                    if ($fullClassName) {
                         $reflection = new \ReflectionClass($fullClassName);
                         $modals[] = $reflection->newInstance();
                    }
                }
            }
        }

        return $modals;
    }

    protected function getFullClassNameFromFile(string $filePath): ?string {
        $contents = file_get_contents($filePath);
        $namespace = null;
        $class = null;

        if (preg_match('/namespace\s+(.+?);/', $contents, $m)) {
            $namespace = $m[1];
        }
        if (preg_match('/class\s+(\w+)/', $contents, $m)) {
            $class = $m[1];
        }

        if ($class) {
            return $namespace ? $namespace . '\\' . $class : $class;
        }

        return null;
    }

    public function getModalDirectory(): string
    {
        return $this->modal_directory;

    }

}