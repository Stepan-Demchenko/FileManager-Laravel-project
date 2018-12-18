<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Traits\ContentTrait;
use App\Traits\HelperTrait;
use Intervention\Image\Facades\Image;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileManagerController extends Controller
{
    const DISK = 'local';
    use ContentTrait, HelperTrait;

    public function content(Request $request)
    {
        if (!$this->checkPath(self::DISK, '/public')) {
            return $this->notFoundMessage();
        }
        if (!$request->input('url')) {
            $contents = $this->getContent(self::DISK, '/public');
            return response()->json(
                $contents
            );
        } else {
            $contents = $this->getContent(self::DISK, $request->input('url'));
            return response()->json(
                $contents
            );
        }
    }


    public function tree()
    {
        // get directories for the directories tree
        $directories = $this->getDirectoriesTree(self::DISK, 'public');

        return response()->json([
            'result' => [
                'status' => 'success',
                'message' => null
            ],
            'directories' => $directories
        ]);
    }

    public function createDirectory(Request $request)
    {
        // path for new directory
        $directoryName = $this->newDirectoryPath($request->input('url'), $request->input('name'));
        // check - exist directory or no
        if (Storage::disk(self::DISK)->exists($directoryName)) {
            return \response()->json([
                'result' => [
                    'status' => 'warning',
                    'message' => trans('file-manager::response.dirExist')
                ]
            ]);
        } else {
            // create new directory
            Storage::disk(self::DISK)->makeDirectory($directoryName);

            // get directory properties
            $directoryProperties = $this->directoryProperties(self::DISK, $directoryName);

            // add directory properties for the tree module
            $tree = $directoryProperties;
            $tree['props'] = ['hasSubdirectories' => false];

            return response()->json([
                'result' => [
                    'status' => 'success',
                    'message' => trans('file-manager::response.dirCreated')
                ]]);
        }
    }

    public function delete(Request $request)
    {
        // check all files and folders - exists or no
        $allItemsExists = true;

        foreach ($request->input('items') as $item) {
            if (!Storage::disk(self::DISK)->exists($item['path'])) {
                $allItemsExists = false;
            }
        }

        if (!$allItemsExists) {
            return response()->json([
                'result' => [
                    'status' => 'danger',
                    'message' => 'NotFound'
                ]
            ]);
        }

        // delete files and folders
        foreach ($request->input('items') as $item) {
            if ($item['type'] === 'dir') {
                // delete directory
                Storage::disk(self::DISK)->deleteDirectory($item['path']);
            } else {
                // delete file
                Storage::disk(self::DISK)->delete($item['path']);
            }
        }

        return response()->json([
            'result' => [
                'status' => 'success',
                'message' => trans('file-manager::response.deleted')
            ]
        ]);
    }

    public function rename(Request $request)
    {
        //Проверка на существовнаие файла со старым именем
        $oldName = $request->input('oldName');
        $newName = $request->input('newName');
        if (!$this->checkPath(self::DISK, $oldName)) {
            return $this->notFoundMessage();
        }
        // Переименование
        Storage::disk(self::DISK)->move($oldName, $newName);

        return response()->json([
            'result' => [
                'status' => 'success',
                'message' => trans('file-manager::response.renamed')
            ]
        ]);
    }

//Предпросмотр картинки
    public function preview(Request $request)
    {
        // disk or path not found
        if (!$this->checkPath(self::DISK, $request->input('path'))) {
            abort(404, trans('file-manager::response.fileNotFound'));
        }
        // get image
        $preview = Image::make(Storage::disk(self::DISK)->get($request->input('path')));
        return $preview->response();
    }

    public function download(Request $request)
    {
        // Проверка на существование диска или файла
        if (!$this->checkPath(self::DISK, $request->input('path'))) {
            abort(404, trans('file-manager::response.fileNotFound'));
        }
        return Storage::disk(self::DISK)->download($request->input('path'));
    }

    public function upload($path, $files, $overwrite)
    {
        if (!$this->checkPath(self::DISK, $path)) {
            return $this->notFoundMessage();
        }

        foreach ($files as $file) {
            // skip or overwrite files
            if (!$overwrite) {
                // if file exist, take next file
                if (Storage::disk(self::DISK)->exists($path . '/' . Input::file($file)->getClientOriginalName())) continue;
            }

            // overwrite or save file
            Storage::disk(self::DISK)->putFileAs(
                $path,
                $file,
                $file->getClientOriginalName()
            );
        }

        return [
            'result' => [
                'status' => 'success',
                'message' => trans('file-manager::response.uploaded')
            ]
        ];
    }

}
