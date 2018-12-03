<?php

namespace App\Traits;
/**
 * Created by PhpStorm.
 * User: Stepan
 * Date: 28-Nov-18
 * Time: 11:16 AM
 */

use Storage;

trait HelperTrait
{
    /**
     * Check Disk and Path
     * @param $disk
     * @param $path
     * @return bool
     */
    public function checkPath($disk, $path)
    {
        // check path
        if ($path && !Storage::disk($disk)->exists($path)) {
            return false;
        }

        return true;
    }

    /**
     * Disk/path not found message
     * @return array
     */
    public function notFoundMessage()
    {
        return [
            'result' => [
                'status' => 'danger',
                'message' => trans('file-manager::response.notFound')
            ]
        ];
    }

    /**
     * Create path for new directory
     * @param $path
     * @param $name
     * @return string
     */
    public function newDirectoryPath($path, $name)
    {
        if (!$path) {
            return $name;
        }

        return $path . '/' . $name;
    }

    /**
     * Rename path - for copy / cut operations
     * @param $itemPath
     * @param $recipientPath
     * @return string
     */
    public function renamePath($itemPath, $recipientPath)
    {
        if ($recipientPath) {
            return $recipientPath . '/' . basename($itemPath);
        }

        return basename($itemPath);
    }

    /**
     * Transform path name
     * @param $itemPath
     * @param $recipientPath
     * @param $partsForRemove
     * @return string
     */
    public function transformPath($itemPath, $recipientPath, $partsForRemove)
    {
        $elements = array_slice(explode('/', $itemPath), $partsForRemove);

        if ($recipientPath) {
            return $recipientPath . '/' . implode('/', $elements);
        }

        return implode('/', $elements);
    }
}