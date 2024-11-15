<?php

namespace Modules\Media\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use Modules\Ihelpers\Http\Controllers\Api\BaseApiController;
use Modules\Media\Entities\File;

// Base Api
use Modules\Media\Repositories\FileRepository;

class MediaController extends BaseApiController
{
  /**
   * @var FileRepository
   */
  private $file;

  public function __construct(FileRepository $file)
  {
    $this->file = $file;
  }

  /**
   * GET A ITEM
   *
   * @return mixed
   */
  public function show($criteria, Request $request)
  {
    try {
      //Get Parameters from URL.
      $params = $this->getParamsRequest($request);
      $originalFileId = $request->input('originalFileId') ?? null;
      $fileCriteria = $originalFileId ?? $criteria;
      //Get the file by id
      $file = File::where('id', $fileCriteria)->orWhere('filename', $fileCriteria)->first();
      // Validate the file token
      $token = $request->input('token');
      $validToken = $token ? $file->validateToken($token) : false;
      //If the token is invalid then verify the session
      if (!$validToken) {
        //Get the current user Id
        $userId = \Auth::id();
        //Validate the permission indexAll of the user
        $hasPermissionIndexAll = $params->permissions['media.medias.index-all'] ?? false;
        //Validate if the current user has acces to the file
        if (($file->created_by != $userId) && !$hasPermissionIndexAll) {
          $file = null;
        }
      }
      // Validate if the file was found. the file can exist,
      // but after validations of token and/or session it may turn to null
      if (!$file) {
        throw new \Exception('Item not found', 404);
      }
      //Prepare and response the file
//            $type = $file->mimetype;
      $privateDisk = config('filesystems.disks.privatemedia');
      $path = $privateDisk['root'] . config('asgard.media.config.files-path') . ($originalFileId ? $criteria : $file->filename);
      //Response
      return \Storage::disk($file->disk ?? 'publicmedia')->response($file->path->getRelativeUrl());
    } catch (\Exception $e) {
      return abort(404);
    }
  }
}
