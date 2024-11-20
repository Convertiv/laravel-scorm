<?php

namespace Convertiv\Scorm\Manager;

use Exception;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Convertiv\Scorm\Exception\StorageNotFoundException;

class ScormDisk
{
    /**
     * Extract zip file into destination directory.
     *
     * @param UploadedFile|string $file zip source
     * @param string $path The path to the destination.
     *
     * @return bool true on success, false on failure.
     */
    function unzipper($file, $target_dir)
    {
        $target_dir = $this->cleanPath($target_dir);
        $unzipper = resolve(\ZipArchive::class);
        if ($unzipper->open($file)) {
            /** @var FilesystemAdapter $disk */
            $disk = $this->getDisk();
            for ($i = 0; $i < $unzipper->numFiles; ++$i) {
                $zipEntryName = $unzipper->getNameIndex($i);
                $destination = $this->join($target_dir, $this->cleanPath($zipEntryName));
                if ($this->isDirectory($zipEntryName)) {
                    $disk->createDirectory($destination);
                    continue;
                }
                $disk->writeStream($destination, $unzipper->getStream($zipEntryName));
            }
            return true;
        }
        return false;
    }

    public function cleanScorm($uuid)
    {
        // Once its unzipped, make some known changes
        // Read the publishSettings.js
        $storageDisk = $this->getDisk();
        if ($storageDisk->exists($uuid . '/publishSettings.js')) {
            $publishSettings = $storageDisk->get($uuid . '/publishSettings.js');
            $publishSettings = str_replace([
                "https://lxp.easygenerator.com",
                "https://nps.easygenerator.com",
                "https://auth.easygenerator.com",
                "https://learn.easygenerator.com",
                "https://progress-storage.easygenerator.com",
                "pdf-converter.easygenerator.com",
                "https://bcc30a12324e461a87d344637a02a4b0@sentry.io/1264190",
                "live.easygenerator.com",
                "https://learn.easygenerator.com/branding-page",
                'https://reports.easygenerator.com'
            ], config('app.url') . '/api', $publishSettings);
            $storageDisk->put($uuid . '/publishSettings.js', $publishSettings);
        }
    }

    /**
     * @param string $file SCORM archive uri on storage.
     * @param callable $fn function run user stuff before unlink
     */
    public function readScormArchive($file, callable $fn)
    {
        try {
            if (Storage::exists($file)) {
                Storage::delete($file);
            }
            // Storage::->writeStream($file, $this->getArchiveDisk()->readStream($file));
            $path = $this->getArchiveDisk()->path($file);
            call_user_func($fn, $path);
            // Clean local resources
            $this->clean($file);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            throw new StorageNotFoundException($ex->getMessage());
        }
    }

    /**
     * Remove unneeded files
     *
     * @param [type] $file
     * @return void
     */
    private function clean($file)
    {
        try {
            // Storage::disk(config('scorm.archive'))->delete($file);
            // Storage::disk('local')->deleteDirectory(dirname($file)); // delete temp dir
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
        }
    }

    /**
     * @param string $directory
     * @return bool
     */
    public function deleteScorm($uuid)
    {
        $this->deleteScormArchive($uuid); // try to delete archive if exists.
        return $this->deleteScormContent($uuid);
    }

    /**
     * @param string $directory
     * @return bool
     */
    private function deleteScormContent($folderHashedName)
    {
        try {
            return $this->getDisk()->deleteDirectory($folderHashedName);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
        }
    }

    /**
     * @param string $directory
     * @return bool
     */
    private function deleteScormArchive($uuid)
    {
        try {
            return $this->getArchiveDisk()->deleteDirectory($uuid);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
        }
    }

    /**
     * 
     * @param array $paths
     * @return string joined path
     */
    private function join(...$paths)
    {
        return  implode(DIRECTORY_SEPARATOR, $paths);
    }

    private function isDirectory($zipEntryName)
    {
        return substr($zipEntryName, -1) ===  '/';
    }

    private function cleanPath($path)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * @return FilesystemAdapter $disk
     */
    private function getDisk()
    {
        if (!config()->has('filesystems.disks.' . config('scorm.disk'))) {
            throw new StorageNotFoundException('scorm_disk_not_define');
        }
        return Storage::disk(config('scorm.disk'));
    }

    /**
     * @return FilesystemAdapter $disk
     */
    private function getArchiveDisk()
    {
        if (!config()->has('filesystems.disks.' . config('scorm.archive'))) {
            throw new StorageNotFoundException('scorm_archive_disk_not_define');
        }
        return Storage::disk(config('scorm.archive'));
    }
}
