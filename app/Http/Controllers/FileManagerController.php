<?php

namespace App\Http\Controllers;

use Chumper\Zipper\Zipper;
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

    public function changeDirectory(Request $request)
    {
        $fromPath = current($request->input('contents.*.path'));
        $toNewPath = current($request->input('secondContents.*.path'));
        $elementMove = current($request->input('secondContents.*.basename'));
        if (Storage::disk(self::DISK)->exists($fromPath) && Storage::disk(self::DISK)->exists($toNewPath)) {
            Storage::move($toNewPath, $fromPath . '/' . $elementMove);
            return response()->json([
                'result' => [
                    'message' => 'success'
                ]]);
        } else {
            return response()->json([
                'result' => [
                    'message' => 'Директорії не існує',
                ], abort(403)]);
        }
    }

    public function createDirectory(Request $request)
    {
        // path for new directory
        $directoryName = $this->newDirectoryPath($request->input('url'), $request->input('name'));
        // check - exist directory or no
        if (Storage::disk(self::DISK)->exists($directoryName)) {
            return \response()->json([
                'result' => [
                    'message' => 'Така директорія вже існує',
                ], abort(403)
            ]);
        } else {
            // create new directory
            Storage::disk(self::DISK)->makeDirectory($directoryName);
            return response()->json([
                'result' => [
                    'status' => 'success',
                    'message' => trans('file-manager::response.dirCreated')
                ]]);
        }
    }

    public function delete(Request $request)
    {
        $allItemsExists = true;
        foreach ($request->input('contents') as $item) {
            if (!Storage::disk(self::DISK)->exists($item['path'])) {
                $allItemsExists = false;
            }
        }
        if (!$allItemsExists) {
            return response()->json([
                'result' => [
                    'status' => 'Файли/файл не найдені'
                ], abort(404)
            ]);
        }
        // delete files and folders
        foreach ($request->input('contents') as $item) {
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
        $oldName = $request->input('path');
        $newName = $request->input('newName');
        if (!$this->checkPath(self::DISK, $oldName)) {
            return $this->notFoundMessage();
        }
        // Переименование
        Storage::disk(self::DISK)->move($oldName, $newName);

        return response()->json([
            'result' => [
                'status' => 'success',
                'message' => 'Успішно перейменован'
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
        $path = current($request->input('contents.*.path'));
        $type = current($request->input('contents.*.type'));
        // Проверка на существование  файла или файла
        if (!$this->checkPath(self::DISK, $request->input($path))) {
            return response()->json([
                'result' => [
                    'status' => 'Файли/файли не найдені'
                ], abort(404)
            ]);
        }
        if ($type == 'dir') {
            $namePathZip = current($request->input('contents.*.basename')) . '.zip';
            $files = glob(storage_path('app/') . $path);
            \Zipper::make(storage_path($namePathZip))->add($files)->close();
            return response()->download(storage_path($namePathZip));
        } else {
            return Storage::disk(self::DISK)->download($path);
        }
    }


    public function upload(Request $request)
    {

        $path = $request->input('url');
        $files = $request->allFiles();
        if (!$this->checkPath(self::DISK, '/' . $path)) {
            return response()->json([
                'result' => [
                    'status' => 'Не знайдено папку'
                ], abort(404)
            ]);
        }
        foreach ($files as $file) {
            Storage::disk(self::DISK)->putFileAs(
                '/' . $path,
                $file,
                $file->getClientOriginalName()
            );
        }

        return [
            'result' => [
                'message' => 'Завантажено',
            ]
        ];
    }

    public function copy(Request $request)
    {
        $path = '/' . current($request->input('contents.*.path'));
        $copyTo = '/' . current($request->input('secondContents.*.path'));
        $name = '/' . current($request->input('contents.*.basename'));
        if (!$this->checkPath(self::DISK, $path) && !$this->checkPath(self::DISK, $copyTo)) {
            return response()->json([
                'result' => [
                    'status' => 'Не знайдено папку'
                ], abort(404)
            ]);
        }
        Storage::copy($path, $copyTo . $name);
        return response()->json([
            'result' => [
                'message' => 'Файл скопійовано!'
            ]
        ]);
    }

}
