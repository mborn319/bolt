<?php

namespace Bolt\Extension;

use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Pimple as Container;

/**
 * Automatic translation inclusion for an extension based upon three factors.
 *  - All translations are in the translations dub-directory of the extension
 *  - Translations are named as en.yml, en_GB.yml, or etc... based upon the locale
 *
 * @author Aaron Valandra <amvalandra@gmail.com>
 */
trait TranslationTrait
{

    /** @var array $translations */
    private $translations;

    /**
     * Call this in register method.
     *
     * @internal
     */
    final protected function extendTranslatorService()
    {
        $app = $this->getContainer();

        $app['translator'] = $app->share(
            $app->extend(
                'translator',
                function ($translator) {
                    $this->loadTranslationsFromDefaultPath();
                    if ($this->translations === null) {
                        return $translator;
                    }
                    foreach ($this->translations as $translation) {
                        $translator->addResource($translation[0], $translation[1], $translation[2]);
                    }

                    return $translator;
                }
            )
        );

    }

    /**
     * Load translations from every extensions translations directory.
     *
     * File names must follow common naming conventions, e.g.:
     *   - en.yml
     *   - en_GB.yml
     */
    private function loadTranslationsFromDefaultPath()
    {
        $app = $this->getContainer();
        /** @var DirectoryInterface $baseDir */
        $baseDir = $this->getBaseDirectory();
        /** @var Filesystem $filesystem */
        $filesystem = $this->getBaseDirectory()->getFilesystem();
        if ($filesystem->has($baseDir->getFullPath() . '/translations') === false) {
            return;
        }
        /** @var Local $local */
        $local = $filesystem->getAdapter();
        $basePath = $local->getPathPrefix();
        /** @var DirectoryInterface $translationDirectory */
        $translationDirectory = $filesystem->get($baseDir->getFullPath() . '/translations');
        foreach ($translationDirectory->getContents(true) as $fileInfo) {
            if ($fileInfo->isFile() === false) {
                continue;
            }

            $extension = $fileInfo->getExtension();
            $path = $basePath . $fileInfo->getPath();
            $domain = $fileInfo->getFilename('.' . $extension);

            $this->translations[] = [$extension, $path, $domain];
        }
    }

    /** @return Container */
    abstract protected function getContainer();

    /** @return DirectoryInterface */
    abstract protected function getBaseDirectory();
}