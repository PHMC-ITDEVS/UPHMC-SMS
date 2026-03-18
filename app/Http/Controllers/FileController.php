<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

use App\Library\Helper;

use App\Models\FileUpload;
use App\Models\CompanyStorage;

class FileController extends Controller
{
    public static function saveImage($id, $folder_name, $base64): void
    {
        static::uploadImage($id, $folder_name, $base64);
    }

    public static function fileUpload($id, $file_name, $folder_name, $base64): void
    {
        // Backward-compatible alias used by legacy call sites.
        static::uploadImage($id, $folder_name, $base64);
    }

    public static function uploadImage($id, $folder_name, $base64)
    {
        try
        {
            Log::info("[UPLOAD_IMAGE][$folder_name][$id]");
            $file = static::decodeBase64Payload($base64);
            $extension = static::extractBase64Extension($base64);

            Storage::disk('local')->put("private/$folder_name/$id.$extension", $file);
        }
        catch(\Exception $ex)
        {
            Log::error("[UPLOAD_IMAGE][$folder_name][$id]");
            Log::error($ex);
        }
    }

    public function viewImage($folder, $file_id, Request $request)
    {
        $is_accounts = (bool)($folder == 'accounts');
        $file_id = $is_accounts ? $file_id : Helper::decrypt($file_id);
        Log::info("[VIEW_IMAGE1][$folder][$file_id]");

        $file_path = null;
        foreach (['jpg', 'jpeg', 'png', 'svg', 'webp'] as $extension) 
        {
            $candidate = "private/$folder/$file_id.$extension";
            if (Storage::disk()->exists($candidate)) {
                $file_path = $candidate;
                break;
            }
        }

        if ($file_path === null){
            return response()->file("images/default-user.jpg");
        }

        return $this->streamStoredFile($file_path);
    }

    public static function uploadFile($table, $id, $file_name, $original_name, $file, $is_base64=true)
    {
        try
        {
            $size = 0;
            $mime_type = "";
            $extension = "";
            $nfile = "";

            if($is_base64)
            {
                $replace = substr($file, 0, strpos($file, ',')+1);
                $nfile = str_replace($replace, '', $file); 
                $nfile = str_replace(' ', '+', $nfile); 
                $nfile = base64_decode($nfile);

                $extension = explode('/', explode(':', substr($file, 0, strpos($file, ';')))[1])[1];

                $f = finfo_open();
                $mime_type = finfo_buffer($f, $nfile, FILEINFO_MIME_TYPE);
                $size = static::getBase64ImageSize($file);
            }
            else
            {
                $size = (int) $file->getSize();
                $extension = strtolower($file->getClientOriginalExtension());
            }

            $data = new FileUpload;
            $data->table = $table;
            $data->table_id = $id;
            $data->name = $file_name;
            $data->original_name = $original_name;
            $data->size = $size;
            $data->mime_type = $mime_type;
            $data->extension = $extension;
            $data->save();

            Storage::disk()->put("private/$table/$id/$file_name.$data->extension", $nfile);
        }
        catch(\Exception $ex)
        {
            Log::error($ex);
        }
    }

    public static function cleanDirectory($table, $id)
    {
        try
        {
            $path = storage_path("app/private/$table/$id");
            File::cleanDirectory($path);

            $base_query = FileUpload::query()
                ->where('table', $table)
                ->where('table_id', $id);

            $upload_size = $base_query->sum('size'); //calculate the sum of the size
            $uploads = $base_query->delete(); //delete previous uploads

        }
        catch(\Exception $ex)
        {
            Log::error($ex);
        }
    }

    public function viewFile(Request $request, $folder_name, $id)
    {
        $extension = $request->extension ? $request->extension : "jpg";
        $name = Helper::decrypt($id);

        $file_path = "private/$folder_name/$name.$extension";
        $exists = Storage::disk()->exists($file_path);

        if(!$exists)
        {
            return "";
        }

        return $this->streamStoredFile($file_path);
    }

    public function viewFolderUpload(Request $request, $folder_name, $folder_id, $id)
    {
        $extension = "";
        $name = Helper::decrypt($id);

        $file_path = "private/$folder_name/$folder_id/$name$extension";
        $exists = Storage::disk()->exists($file_path);

        if(!$exists)
        {
            return "";
        }

        return $this->streamStoredFile($file_path);
    }

    public function viewExcel($date, $file_name)
    {
        $file_path = "excel/$date/$file_name";
        $exists = Storage::disk()->exists($file_path);

        if(!$exists)
        {
            abort(404);
        }

        return $this->streamStoredFile($file_path);
    }

    private function streamStoredFile(string $file_path)
    {
        $file = Storage::disk()->get($file_path);
        $type = Storage::mimeType($file_path);

        $response = Response::make($file, 200);
        $response->header("Content-Type", $type);
        return $response;
    }

    private static function decodeBase64Payload(string $base64): string
    {
        $normalized = $base64;
        $commaPos = strpos($base64, ',');
        if ($commaPos !== false) {
            $replace = substr($base64, 0, $commaPos + 1);
            $normalized = str_replace($replace, '', $base64);
        }
        $normalized = str_replace(' ', '+', $normalized);
        return base64_decode($normalized);
    }

    private static function extractBase64Extension(string $base64): string
    {
        if (preg_match('/^data:image\/([a-zA-Z0-9.+-]+);base64,/', $base64, $matches)) {
            $extension = strtolower($matches[1]);
            if ($extension === 'jpg') return 'jpg';
            if ($extension === 'jpeg') return 'jpeg';
            if ($extension === 'png') return 'png';
            if ($extension === 'svg+xml') return 'svg';
            if ($extension === 'webp') return 'webp';
        }

        return 'jpg';
    }

    private static function getBase64ImageSize($base64Image) // return memory size in B, KB, MB
    {
        try
        {
            $size_in_bytes = (int) (strlen(rtrim($base64Image, '=')) * 3 / 4);
            $size_in_kb = $size_in_bytes / 1024;
            $size_in_mb = $size_in_kb / 1024;
    
            return round($size_in_mb, 10);
        }
        catch(\Exception $e)
        {
            return $e;
        }
    }

    public static function getTotalFileSize($uploads) // return memory size in B, KB, MB
    {
        try
        {
            $total_size = 0;
            foreach ($uploads as $file)
            {
                $upload = $file;
                if(is_array($upload) && array_key_exists('data', $upload) && $upload['data']) $upload = $file['data'];

                $size_in_bytes = (int) (strlen(rtrim($upload, '=')) * 3 / 4);
                $size_in_kb = $size_in_bytes / 1024;
                $size_in_mb = $size_in_kb / 1024;

                $total_size += round($size_in_mb, 10);
            }
            return $total_size;
        }
        catch(\Exception $e)
        {
            return $e;
        }
    }

    public static function getInitialFileSize($table, $id)
    {
        $data = FileUpload::query()
            ->where('table', $table)
            ->where('table_id', $id)
            ->sum('size');

        return $data;
    }
}
